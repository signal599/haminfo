Drupal.hamApp = (Drupal, hsSettings) => {
  const formElement = document.querySelector('.ham-map-form');
  const mapContainer = document.querySelector('.map-container');
  let googleMap;
  const mapMarkers = new Map();
  let placesLocation;
  let MarkerConstructor;
  let centerMovedTimerId;
  let setCenterEnabled = false;
  let centerChangedEnabled = false;
  let activeInfoWindow;
  let rectangles = [];

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

  const mapCenterChanged = () => {
    if (!centerChangedEnabled) {
      centerChangedEnabled = true;
      return;
    }

    if (centerMovedTimerId) {
      clearTimeout(centerMovedTimerId);
    }

    centerMovedTimerId = setTimeout(() => {
      const location = googleMap.getCenter();
      setCenterEnabled = false;
      mapAjaxRequest({queryType:'latlng', value:`${location.lat()},${location.lng()}}`}, false);
    }, 2000);
  }

  const createGoogleMap = async () => {
    const [{ Map }, { AdvancedMarkerElement }, { PlaceAutocompleteElement }] = await Promise.all([
      google.maps.importLibrary('maps'),
      google.maps.importLibrary('marker'),
      google.maps.importLibrary('places')
    ]);

    MarkerConstructor = AdvancedMarkerElement;

    googleMap = new Map(mapContainer, {
      zoom: 14,
      zoomControl: true,
      mapId: 'ham-stations',
    });

    const placeAutocomplete = new PlaceAutocompleteElement({includedRegionCodes: ['us']});
    const addressWrapper = formElement.querySelector('.query-address');
    const oldInput = addressWrapper.querySelector('input');
    const oldId = oldInput.id;
    addressWrapper.replaceChild(placeAutocomplete, oldInput);
    placeAutocomplete.id = oldId;

    placeAutocomplete.addEventListener('gmp-select', async ({ placePrediction }) => {
      const place = placePrediction.toPlace();
      await place.fetchFields({ fields: ['location'] });
      placesLocation = place.location;
      submitQuery();
    });

    googleMap.addListener('center_changed', mapCenterChanged);
  }

  const setMapCenter = (response) => {
    centerChangedEnabled = false;
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

      // Add new markers.
      response.locations.forEach(location => {
        if (!mapMarkers.has(location.id)) {
          drawMarker(location);
        }
      });

      openQueriedCallsign(response);
    }

    const drawMarker = (location) => {
      if (location.addresses.length === 0) {
        return;
      }

      // Workaround because AdvancedMarkerElement doesn't have labels like legacy did.
      let glyphLabel = document.createElement('div');
      glyphLabel.style = 'color: #000000; font-size: 14px;';
      glyphLabel.innerText = markerLabel(location);
      let iconImage = new google.maps.marker.PinElement({
        glyph: glyphLabel,
      });

      const marker = new MarkerConstructor({
        position: {lat: location.lat, lng: location.lng},
        map: googleMap,
        content: iconImage.element
      });

      mapMarkers.set(location.id, marker);

      marker.addListener('click', e => {
        openInfoWindow(location, marker);
      });
    }

    const openQueriedCallsign = (response) => {
      if (response.queryCallsignIdx === null) {
        return;
      }

      const location = response.locations[response.queryCallsignIdx];
      const marker = mapMarkers.get(location.id);

      openInfoWindow(location, marker);
    }

  const openInfoWindow = (location, marker) => {
    closeInfoWindow();

    const addresses = [];
    const lastIndex = location.addresses.length - 1;
    const multi = location.addresses.length > 1;

    location.addresses.forEach((address, index) => {
      let classes = ['address'];
      if (multi) {
        if (index === 0) {
          classes.push('first');
        }
        else if(index === lastIndex)
        {
          classes.push('last');
        }
      }
      addresses.push(`<div class="${classes.join(' ')}">${writeAddress(address)}</div>`);
    });

    let classes = ['infowindow'];
    if (multi) {
      classes.push('multi');
    }

    const infoWindow = new google.maps.InfoWindow({
      content: `<div class="${classes.join(' ')}">${addresses.join('')}</div>`,
      zIndex: 99
    });

    infoWindow.open(googleMap, marker);
    activeInfoWindow = infoWindow;

    infoWindow.addListener('closeclick', () => {
      activeInfoWindow = null;
    });
  };

  const closeInfoWindow = () => {
    if (activeInfoWindow) {
      activeInfoWindow.close();
      activeInfoWindow = null;
    }
  }

  const writeAddress = (address) => {
    const stations = [];
    const lastIndex = address.stations.length - 1;
    const multi = address.stations.length > 1;

    address.stations.forEach((station, index) => {
      let classes = ['station'];
      if (multi) {
        if (index === 0) {
          classes.push('first');
        }
        else if(index === lastIndex)
        {
          classes.push('last');
        }
      }
      stations.push(`<div class="${classes.join(' ')}">${writeStation(station)}</div>`)
    });

    const address2 = address.address2 ? address.address2 + '<br>' : '';

    return `<div class="stations">${stations.join('')}</div><div>
      ${address.address1}<br>
      ${address2}
      ${address.city}, ${address.state} ${address.zip}</div>`;
  }

  const writeStation = (station) => {
    const opclass = station.operatorClass ? (' ' + station.operatorClass) : '';

    return `
    <span>${station.callsign}</span> <a href="https://www.qrz.com/db/${station.callsign}" target="_blank">qrz.com</a>${opclass}<br>
    ${station.name}`;
  }

  const clearRectangles = () => {
    rectangles.forEach((el, index) => {
      rectangles[index].setMap(null);
      rectangles[index] = null;
    });

    rectangles = [];
  }

  const drawGridsquares = (response, show) => {
    clearRectangles();

    if (show) {
      response.subsquares.forEach(x => x.forEach(y => drawGridsquare(y)));
    }
  }

  const drawGridsquare = (subsquare) => {
    const rectangle = new google.maps.Rectangle({
      strokeColor: '#000000',
      strokeOpacity: 0.5,
      strokeWeight: 1,
      fillOpacity: 0,
      map: googleMap,
      bounds: {
        north: subsquare.latNorth,
        south: subsquare.latSouth,
        east: subsquare.lngEast,
        west: subsquare.lngWest
      }
    });

    rectangles.push(rectangle);
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
      return {error: ''};
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
    console.log(response);
    closeInfoWindow();
    setLocationsMap(response);

    if (setCenterEnabled) {
      setMapCenter(response);
    }

    drawMarkers(response);
    drawGridsquares(response, true);
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
    showError('');
    query = validateAndBuildQuery();

    if (query.error) {
      showError(query.error);
    }
    else {
      setCenterEnabled = true;
      mapAjaxRequest(query);
    }
  };

  formElement.addEventListener('submit', event => {
    event.preventDefault();
    submitQuery(true);
  });

  const addressRadio = formElement.querySelector('input[name=query_type][value=a]');
  addressRadio.disabled = true;
  formElement.querySelector('.query-other input').focus();
  createGoogleMap();
  addressRadio.disabled = false;
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
