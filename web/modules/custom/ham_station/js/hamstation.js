Drupal.hamApp = (Drupal, hsSettings) => {
  const formElement = document.querySelector('.ham-map-form');
  const mapContainer = document.querySelector('.map-container');
  let googleMap;
  const mapMarkers = new Map();
  let placesLocation;
  let addressKeyCode;

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

  const setLocationsMap = (response) => {
    const map = new Map();
    response.locations.forEach(location => {
      map.set(location.id, true);
    });

    response.locationsMap = map;
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
        if (!response.locationsMap.has(id)) {
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

  const validateAndBuildQuery = () => {
    const queryType = getQueryType();
    let query;

    if ('cgz'.indexOf(queryType) > -1) {
      const element = formElement.querySelector('input[name=query');
      const value = element.value.trim();

      if (queryType === 'c') {
        query = buildCallsignQuery(value);
      }
      else if (queryType === 'g') {
        query = buildGridsquareQuery(value);
      }
      else if (queryType === 'z') {
        query = buildZipcodeQuery(value);
      }

      if (!query.error) {
        element.value = query.value;
      }
    }
    else {
      query = buildAddressQuery();
    }

    return query;
  };

  const buildCallsignQuery = (value) => {
    value = value.toUpperCase();

    return value
      ? {queryType: 'c', value}
      : {error: 'Please enter a callsign.'};
  };

  const buildGridsquareQuery = (value) => {
    if (!value.match(/^[A-R]{2}\d\d[a-x]{2}$/i)) {
      return {error: 'Please enter a six character gridsquare.'};
    }

    return {
      queryType:'g',
      value: value.substring(0, 2).toUpperCase() + value.substring(2, 4) + value.substring(4).toLowerCase(),
    };
  }

  const buildZipcodeQuery = (value) => {
    if (!value.match(/^\d{5}$/)) {
      return {error: 'Please enter a five digit zip code.'};
    }

    return {queryType:'z', value};
  }

  const buildAddressQuery = () => {
    if (!placesLocation) {
      return {error: 'qq'};
    }

    return {
      queryType:'latlng',
      value: `${placesLocation.lat()},${placesLocation.lng()}}`
    }
  }

  const showError = (error) => {
    const element = formElement.querySelector('.error-message');
    element.innerHTML = error;
    if (error) {
      element.classList.remove('hidden');
    }
    else {
      element.classList.add('hidden');
    }
  }

  const processSuccessResponse = (response) => {
    setLocationsMap(response);
    drawMarkers(response);
    setMapCenter(response);
    mapContainer.classList.remove('hidden');
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
        showError('Sorry, something went wrong.')
        return;
      }

      if (response.result.error) {
        showError(response.result.error);
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

  const submitQuery = () => {
    query = validateAndBuildQuery();
    if (query.error) {
      showError(query.error);
    }
    else {
      mapAjaxRequest(query);
    }
  };

  formElement.querySelector('input[name=address]').addEventListener('keyup', event => {
    addressKeyCode = event.code;
    if (addressKeyCode === 'Enter' && placesLocation) {
      submitQuery();
    }
  });

  formElement.addEventListener('submit', event => {
    event.preventDefault();
    if (document.activeElement.getAttribute('id') !== 'edit-address') {
      submitQuery();
    }
  });

  const setupPlaces = () => {
    const places = new google.maps.places.Autocomplete(
      formElement.querySelector('input[name=address]')
    );

    places.setFields(['geometry.location']);

    places.addListener('place_changed', () => {
      placesLocation = places.getPlace().geometry.location;
      if (addressKeyCode === 'Enter') {
        submitQuery();
      }
    });
  }

  setupPlaces();
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
