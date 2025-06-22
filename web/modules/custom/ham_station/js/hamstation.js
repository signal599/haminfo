Drupal.hamApp = (Drupal, hsSettings) => {
  const formElement = document.querySelector('.ham-map-form');
  const mapContainer = document.querySelector('.map-container');
  let map;

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

  const createMap = () => {
      if (map) {
        return;
      }

      map = new google.maps.Map(mapContainer, {
        zoom: 14,
        zoomControl: true,
      });

      // map.addListener('center_changed', function () {
      //   mapCenterChangedListener(map.getCenter());
      // });
    }

  const doInitialQuery = () => {

  };

  const refreshMap = () => {
    const ajax = Drupal.ajax({
      url: '/ham-map-ajax',
      httpMethod: 'POST',
      submit: {queryType: 'c', value: 'KT1F'}
    });

    Drupal.AjaxCommands.prototype.hamMapQuery = (ajax, response, status) => {
      if (status === 'success') {
        console.log(response.result);
      }
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
    refreshMap();
  });

  formElement.querySelector('.query-other input').focus();
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
