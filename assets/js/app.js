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

    $(".clickable-row").click(function() {
        window.location = $(this).data("href");
    });
    $(".clickable-row").css('cursor', 'pointer');

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()

        $('#donationIndexTable').DataTable({ "pageLength": 100, "order": [[ 0, "desc" ]]})
        $('#teamIndexTable').DataTable({ "pageLength": 20, "order": [[ 1, "asc" ]]});
        $('#teamVerifyTable').DataTable({ "pageLength": 50, "order": [[ 3, "asc" ]]});        
        $('#campaignAwardIndexTable').DataTable({ "pageLength": 20, "order": [[ 1, "asc" ]]});       
        $('#classroomIndexTable').DataTable({ "pageLength": 50, "order": [[ 3, "asc" ]]});    
        $('#studentIndexTable').DataTable({ "pageLength": 50, "order": [[ 3, "asc" ]]});  
        $('#gradeIndexTable').DataTable({ "pageLength": 50, "order": [[ 1, "asc" ]]});          
        $('#userIndexTable').DataTable({ "pageLength": 50, "order": [[ 4, "desc" ]]});     
        $('#teacherIndexTable').DataTable({ "pageLength": 50, "order": [[ 3, "asc" ]]});  
        $('#donationVerifyTable').DataTable({ "pageLength": 50, "order": [[ 3, "asc" ]]});                  
    })


  // Add class .active to current link
  $.navigation.find('a').each(function(){

    var cUrl = String(window.location).split('?')[0];

    if (cUrl.substr(cUrl.length - 1) == '#') {
      cUrl = cUrl.slice(0,-1);
    }

    if ($($(this))[0].href==cUrl) {
      $(this).addClass('active');

      $(this).parents('ul').add(this).each(function(){
        $(this).parent().addClass('open');
      });
    }
  });

  // Dropdown Menu
  $.navigation.on('click', 'a', function(e){

    if ($.ajaxLoad) {
      e.preventDefault();
    }

    if ($(this).hasClass('nav-dropdown-toggle')) {
      $(this).parent().toggleClass('open');
      resizeBroadcast();
    }

  });

  function resizeBroadcast() {

    var timesRun = 0;
    var interval = setInterval(function(){
      timesRun += 1;
      if(timesRun === 5){
        clearInterval(interval);
      }
      window.dispatchEvent(new Event('resize'));
    }, 62.5);
  }

  /* ---------- Main Menu Open/Close, Min/Full ---------- */
  $('.navbar-toggler').click(function(){

    if ($(this).hasClass('sidebar-toggler')) {
      $('body').toggleClass('sidebar-hidden');
      resizeBroadcast();
    }

    if ($(this).hasClass('sidebar-minimizer')) {
      $('body').toggleClass('sidebar-minimized');
      resizeBroadcast();
    }

    if ($(this).hasClass('aside-menu-toggler')) {
      $('body').toggleClass('aside-menu-hidden');
      resizeBroadcast();
    }

    if ($(this).hasClass('mobile-sidebar-toggler')) {
      $('body').toggleClass('sidebar-mobile-show');
      resizeBroadcast();
    }

  });

  $('.sidebar-close').click(function(){
    $('body').toggleClass('sidebar-opened').parent().toggleClass('sidebar-opened');
  });

  /* ---------- Disable moving to top ---------- */
  $('a[href="#"][data-top!=true]').click(function(e){
    e.preventDefault();
  });

});

/****
* CARDS ACTIONS
*/

$(document).on('click', '.card-actions a', function(e){
  e.preventDefault();

  if ($(this).hasClass('btn-close')) {
    $(this).parent().parent().parent().fadeOut();
  } else if ($(this).hasClass('btn-minimize')) {
    
    if (!$(this).hasClass('collapsed')) {
      $('i',$(this)).removeClass($.panelIconOpened).addClass($.panelIconClosed);
    } else {
      $('i',$(this)).removeClass($.panelIconClosed).addClass($.panelIconOpened);
    }

  } else if ($(this).hasClass('btn-setting')) {
    $('#myModal').modal('show');
  }

});

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function init(url) {

  /* ---------- Tooltip ---------- */
  $('[rel="tooltip"],[data-rel="tooltip"]').tooltip({"placement":"bottom",delay: { show: 400, hide: 200 }});

  /* ---------- Popover ---------- */
  $('[rel="popover"],[data-rel="popover"],[data-toggle="popover"]').popover();

}
