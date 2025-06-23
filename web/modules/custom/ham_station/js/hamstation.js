Drupal.hamApp = (Drupal, hsSettings) => {
  const formElement = document.querySelector('.ham-map-form');
  const mapContainer = document.querySelector('.map-container');
  let googleMap;
  const locations = new Map();
  const mapMarkers = new Map();

  const setQueryType = (queryType) => {
    const labels = {
      c: ['Callsign', 'Enter a callsign.'],
      g: ['Gridsquare', 'Enter a six character grid subsquare.'],
      z: ['Zip code', 'Enter a five digit zip code.']
    };

    const address = formElement.querySelector('.query-address');
    const other = formElement.querySelector('.query-other');

    if ('cgz'.indexOf(queryType) > -1) {
      other.querySelector('label').innerHTML = labels[queryType][0];
      other.querySelector('.description').innerHTML = labels[queryType][1];
      other.classList.remove('hidden');
      address.classList.add('hidden');
      return;
    }

    other.classList.add('hidden');
    address.classList.remove('hidden');
  }

  const getQueryType = () => {
    return formElement.querySelector('input[name=query_type]:checked').value;
  }

  const createGoogleMap = () => {
    googleMap = new google.maps.Map(mapContainer, {
      zoom: 14,
      zoomControl: true,
    });

      // map.addListener('center_changed', function () {
      //   mapCenterChangedListener(map.getCenter());
      // });
  }

  const doInitialQuery = () => {

  };

  const setMapCenter = (response) => {
    googleMap.setCenter({lat: response.mapCenterLat, lng: response.mapCenterLng});
  }

  const setLocations = (response) => {
    locations.clear();
    response.locations.forEach(location => {
      locations.set(location.id, true);
    });
  };

    const getStationCountForLocation = (location) => {
      let stationCount = 0;
      location.addresses.forEach(address => {
        address.stations.forEach(station => stationCount++);
      });

      return stationCount;
    }

    const markerLabel = (location) => {
      const stationCount = getStationCountForLocation(location);
      return location.addresses[0].stations[0].callsign + (stationCount > 1 ? '+' : '');
    }

    const drawMarkers = (response) => {
      // Remove markers for locations no longer on the map.
      for (const [id, marker] of mapMarkers) {
        if (!locations.has(id)) {
          marker.setMap(null);
          mapMarkers.delete(id);
        }
      }

  //      removeQueriedCallsign();

      // Add new markers.
      response.locations.forEach(location => {
        if (!mapMarkers.has(location.id)) {
          drawMarker(location);
        }
      });

  //      openQueriedCallsign();
    }

    const drawMarker = (location) => {
      if (location.addresses.length === 0) {
        return;
      }

      const marker = new google.maps.Marker({
        position: {lat: location.lat, lng: location.lng},
        map: googleMap,
        label: markerLabel(location)
      });

      mapMarkers.set(location.id, marker);

      // marker.addListener('click', e => {
      //   openInfowindow(location, marker);
      // });
    }

  const processSuccessResponse = (response) => {
    setLocations(response);
    drawMarkers(response);
    setMapCenter(response);
    mapContainer.classList.remove('hidden');
  };

  const processErrorResponse = (error) => {
    console.log(error);
  };

  const mapAjaxRequest = (query) => {
    const ajax = Drupal.ajax({
      url: '/ham-map-ajax',
      httpMethod: 'POST',
      submit: query,
      progress: { type: 'throbber', message: 'Processing...' },
      element: formElement.querySelector('.processing'),
    });

    Drupal.AjaxCommands.prototype.hamMapQuery = (ajax, response, status) => {
      if (status !== 'success') {
        processError('Sorry, something went wrong.')
        return;
      }

      console.log(ajax);

      if (response.result.error) {
        processErrorResponse(response.result.error);
        return;
      }

      processSuccessResponse(response.result);
    };

    ajax.execute();
  };

  formElement.addEventListener('change', event => {
    if (event.target.getAttribute('name') === 'query_type') {
       setQueryType(event.target.value);
    }
  });

  formElement.addEventListener('submit', event => {
    event.preventDefault();
    mapAjaxRequest({queryType: 'c', value: 'KT1F'});
  });

  formElement.querySelector('.query-other input').focus();
  createGoogleMap();
};

(function (Drupal, once) {

  Drupal.behaviors.hamstation = {
    attach: (context, settings) => {
      if (context !== document) {
        return;
      }

      if (once('js-ham-station', 'body').length === 0) {
        // Avoid double attach caused by big pipe.
        return;
      }

      Drupal.hamApp(Drupal, settings.ham_station);
    }
  };
})(Drupal, once);
