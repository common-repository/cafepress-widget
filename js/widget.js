window.cpProductsWidget.init = function(){
  
  var liwidth = jQuery('div#cpwidget ul.products').width();
  var ulwidth = liwidth * jQuery('div#cpwidget ul.products li').length;
  jQuery('div#cpwidget ul.products li').each(function(){
    jQuery(this).width(liwidth);
  });

  if (jQuery('div#cpwidget ul.products li').length == 1) return;

  var clicky = function(e){
    var get = jQuery(this).attr('data-get');
    if (get == '') return;

    var url = jQuery(this).attr('href');
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
    jQuery('iframe#cafepress_loader').load(function(){
      window.location = url;
    });

    jQuery('iframe#cafepress_loader').attr('src', 'http://widgets.cafepress.com/poke/wordpress?' + get);
  };
  
  jQuery('div.CafePressWidget ul.products li a').click(clicky);
  jQuery('div.CafePressWidget div.toolbar a').click(clicky);

  jQuery('div#cpwidget ul.products').css({
    "width": ulwidth,
    "left": -200
  });

  try {
    var anav = jQuery('div#cpwidget a.nav');
    anav.css({
      'opacity': 0,
      'display': 'block'
    });
    
    jQuery('div#cpwidget').mouseenter(function(){
      window.cpProductsWidget.is_inside = true;
      
      anav.animate({
        'opacity': 1
      }, 200, 'swing', function(){
        jQuery(this).clearQueue();
      });

    });

    jQuery('div#cpwidget').mouseleave(function(){
      window.cpProductsWidget.is_inside = false;
      
      anav.animate({
        'opacity': 0
      }, 200, 'swing', function(){
        jQuery(this).clearQueue();
      });

    });

    anav.siblings('a.next').click(function(evnt){
      evnt.stopImmediatePropagation();
      window.cpProductsWidget.animate_next();
    });

    anav.siblings('a.prev').click(function(evnt){
      evnt.stopImmediatePropagation();
      window.cpProductsWidget.animate_prev();
    });

  } catch (e) {}

  window.cpProductsWidget.auto_scroll = setInterval(function(){
    if (window.cpProductsWidget.is_inside) return true;
    window.cpProductsWidget.animate_next();
  }, 3000);
  
};

window.cpProductsWidget.animate_runned = false;
window.cpProductsWidget.is_inside = false;

window.cpProductsWidget.animate_next = function (){
  window.cpProductsWidget.animate_runned = true;

  jQuery('div#cpwidget ul.products li:last').after(jQuery('div#cpwidget ul.products li:first'));
  var first = jQuery('div#cpwidget ul.products li:first');
  first.css({
    "margin-left": 200
  });
  first.animate({
    "margin-left": 0
  },  {
    "duration" : 500,
    "easing"  : 'swing',
    "queue" : true,
    "complete" : function(){
      window.cpProductsWidget.animate_runned = false;
    }
  });

  return true;
};

window.cpProductsWidget.animate_prev = function (){
  window.cpProductsWidget.animate_runned = true;

  var last = jQuery('div#cpwidget ul.products li:last');
  last.css('margin-left', -200);
  jQuery('div#cpwidget ul.products li:first').before(last);
  last.animate({
    "margin-left": 0
  }, {
    "duration" : 500,
    "easing"  : 'swing',
    "queue" : true,
    "complete" : function(){
      window.cpProductsWidget.animate_runned = false;
    }
  });

  return true;
};

if (typeof jQuery != 'undefined') {
  window.cpProductsWidget.init();
} else {
  (function() {
  var e = document.createElement('script');
  var f = function(){window.cpProductsWidget.init();}
  e.src = window.cpProductsWidget.path+'/js/jquery-1.5.0.js';
  e.async   = true;
  e.onload  = f;
  e.onreadystatechange = function(){if (e.readyState == 'loaded' || e.readyState == 'complete')  {$.noConflict();f();}};
  document.getElementById('cpwidget').appendChild(e);
  })();
}