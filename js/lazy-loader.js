/**
 *  Script lazy loader 0.5
 *  Copyright (c) 2008 Bob Matsuoka
 *  http://ajaxian.com/archives/a-technique-for-lazy-script-loading
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 */

var LazyLoader = {
  "timer" : {}, // contains timers for scripts
  "load"  : function(url, callback) {
    try {

      var script = document.createElement("script");

      script.src    = url;
      script.async  = true;
      script.type   = "text/javascript";

      $$("head")[0].appendChild(script);  // add script tag to head element

      // was a callback requested
      if (callback) {

        // test for onreadystatechange to trigger callback
        script.onreadystatechange = function () {
          if (script.readyState == 'loaded' || script.readyState == 'complete') {
            callback();
          }
        }

        // test for onload to trigger callback
        script.onload = function () {
          callback();
          return;
        }

      }//end if


    } catch (e) {

      alert(e);

    }//end try

  }
}; //namespace











