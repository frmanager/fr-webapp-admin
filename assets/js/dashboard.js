// ...

import $ from 'jquery';
// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require('popper.js');
require('tether');
require('bootstrap');
require('pace');
require('perfect-scrollbar');
require('datatables.net-bs4');


import '../css/simple-line-icons.css';
import '../css/fontawesome.min.css';
import '../css/style.min.css';
import '../css/template.css';


import '../css/template.css';

// CORE UI
import '../js/libs/coreui.min.js';

//Plugins and scripts required by all views
import '../js/libs/Chart.min.js';

//Custom scripts required by this view -->

//LEGACY -->
import '../js/libs/jquery.countdown.min.js';
import '../js/ie10-viewport-bug-workaround.js';


/*****
* CONFIGURATION
*/
 //Main navigation
  $.navigation = $('nav > ul.nav');

  $.panelIconOpened = 'icon-arrow-up';
  $.panelIconClosed = 'icon-arrow-down';

  //Default colours
  $.brandPrimary =  '#20a8d8';
  $.brandSuccess =  '#4dbd74';
  $.brandInfo =     '#63c2de';
  $.brandWarning =  '#f8cb00';
  $.brandDanger =   '#f86c6b';

  $.grayDark =      '#2a2c36';
  $.gray =          '#55595c';
  $.grayLight =     '#818a91';
  $.grayLighter =   '#d1d4d7';
  $.grayLightest =  '#f8f9fa';

'use strict';

/****
* MAIN NAVIGATION
*/

$(document).ready(function($){

    $('[data-countdown]').each(function() {
      var $this = $(this),
          finalDate = $(this).data('countdown');
      $this.countdown(finalDate, function(event) {
          $this.html(event.strftime('<span class="label label-info">%D</span> day%!D &nbsp;&nbsp;<span class="label label-info">%H</span> hours &nbsp;&nbsp;<span class="label label-info">%M</span> minutes &nbsp;&nbsp;<span class="label label-info">%S</span> seconds &nbsp;&nbsp;remaining'));
      });
  });


  //convert Hex to RGBA
  function convertHex(hex,opacity){
    hex = hex.replace('#','');
    var r = parseInt(hex.substring(0,2), 16);
    var g = parseInt(hex.substring(2,4), 16);
    var b = parseInt(hex.substring(4,6), 16);

    var result = 'rgba('+r+','+g+','+b+','+opacity/100+')';
    return result;
  }

  //Random Numbers
  function random(min,max) {
    return Math.floor(Math.random()*(max-min+1)+min);
  }

  var elements = 16;
  var labels = [];
  var data = [];

  for (var i = 2000; i <= 2000 + elements; i++) {
    labels.push(i);
    data.push(random(40,100));
  }

  var options = {
    maintainAspectRatio: false,
    legend: {
      display: false
    },
    scales: {
      xAxes: [{
        display: false,
        barPercentage: 0.6,
      }],
      yAxes: [{
        display: false,
      }]
    },

  };
  var data = {
    labels: labels,
    datasets: [
      {
        backgroundColor: 'rgba(255,255,255,.3)',
        borderColor: 'transparent',
        data: data
      },
    ]
  };
  var ctx = $('#card-chart1');
  var cardChart1 = new Chart(ctx, {
    type: 'bar',
    data: data,
    options: options
  });


});
