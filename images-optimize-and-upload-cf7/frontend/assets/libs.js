/*
 * JavaScript Canvas to Blob
 * https://github.com/blueimp/JavaScript-Canvas-to-Blob
 *
 * Copyright 2012, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 *
 * Based on stackoverflow user Stoive's code snippet:
 * http://stackoverflow.com/q/4998908
 */

/* global define, Uint8Array, ArrayBuffer, module */

;(function(window) {
  'use strict'

  var CanvasPrototype =
    window.HTMLCanvasElement && window.HTMLCanvasElement.prototype
  var hasBlobConstructor =
    window.Blob &&
    (function() {
      try {
        return Boolean(new Blob())
      } catch (e) {
        return false
      }
    })()
  var hasArrayBufferViewSupport =
    hasBlobConstructor &&
    window.Uint8Array &&
    (function() {
      try {
        return new Blob([new Uint8Array(100)]).size === 100
      } catch (e) {
        return false
      }
    })()
  var BlobBuilder =
    window.BlobBuilder ||
    window.WebKitBlobBuilder ||
    window.MozBlobBuilder ||
    window.MSBlobBuilder
  var dataURIPattern = /^data:((.*?)(;charset=.*?)?)(;base64)?,/
  var dataURLtoBlob =
    (hasBlobConstructor || BlobBuilder) &&
    window.atob &&
    window.ArrayBuffer &&
    window.Uint8Array &&
    function(dataURI) {
      var matches,
        mediaType,
        isBase64,
        dataString,
        byteString,
        arrayBuffer,
        intArray,
        i,
        bb
      // Parse the dataURI components as per RFC 2397
      matches = dataURI.match(dataURIPattern)
      if (!matches) {
        throw new Error('invalid data URI')
      }
      // Default to text/plain;charset=US-ASCII
      mediaType = matches[2]
        ? matches[1]
        : 'text/plain' + (matches[3] || ';charset=US-ASCII')
      isBase64 = !!matches[4]
      dataString = dataURI.slice(matches[0].length)
      if (isBase64) {
        // Convert base64 to raw binary data held in a string:
        byteString = atob(dataString)
      } else {
        // Convert base64/URLEncoded data component to raw binary:
        byteString = decodeURIComponent(dataString)
      }
      // Write the bytes of the string to an ArrayBuffer:
      arrayBuffer = new ArrayBuffer(byteString.length)
      intArray = new Uint8Array(arrayBuffer)
      for (i = 0; i < byteString.length; i += 1) {
        intArray[i] = byteString.charCodeAt(i)
      }
      // Write the ArrayBuffer (or ArrayBufferView) to a blob:
      if (hasBlobConstructor) {
        return new Blob([hasArrayBufferViewSupport ? intArray : arrayBuffer], {
          type: mediaType
        })
      }
      bb = new BlobBuilder()
      bb.append(arrayBuffer)
      return bb.getBlob(mediaType)
    }
  if (window.HTMLCanvasElement && !CanvasPrototype.toBlob) {
    if (CanvasPrototype.mozGetAsFile) {
      CanvasPrototype.toBlob = function(callback, type, quality) {
        var self = this
        setTimeout(function() {
          if (quality && CanvasPrototype.toDataURL && dataURLtoBlob) {
            callback(dataURLtoBlob(self.toDataURL(type, quality)))
          } else {
            callback(self.mozGetAsFile('blob', type))
          }
        })
      }
    } else if (CanvasPrototype.toDataURL && dataURLtoBlob) {
      CanvasPrototype.toBlob = function(callback, type, quality) {
        var self = this
        setTimeout(function() {
          callback(dataURLtoBlob(self.toDataURL(type, quality)))
        })
      }
    }
  }
  if (typeof define === 'function' && define.amd) {
    define(function() {
      return dataURLtoBlob
    })
  } else if (typeof module === 'object' && module.exports) {
    module.exports = dataURLtoBlob
  } else {
    window.dataURLtoBlob = dataURLtoBlob
  }
})(window)

/**
 * Compress.js v2.2.2
 * @url https://github.com/davejm/client-compress
 * @url https://unpkg.com/client-compress@2.2.2/dist/index.js
 */
!function(t,e){"object"==typeof exports&&"object"==typeof module?module.exports=e():"function"==typeof define&&define.amd?define([],e):"object"==typeof exports?exports.Compress=e():t.Compress=e()}(window,function(){return function(t){var e={};function r(n){if(e[n])return e[n].exports;var i=e[n]={i:n,l:!1,exports:{}};return t[n].call(i.exports,i,i.exports,r),i.l=!0,i.exports}return r.m=t,r.c=e,r.d=function(t,e,n){r.o(t,e)||Object.defineProperty(t,e,{configurable:!1,enumerable:!0,get:n})},r.r=function(t){Object.defineProperty(t,"__esModule",{value:!0})},r.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(e,"a",e),e},r.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},r.p="",r(r.s=7)}([function(t,e,r){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.loadImageElement=function(t,e){return new Promise(function(r,n){t.addEventListener("load",function(){r(t)},!1),t.addEventListener("error",function(t){n(t)},!1),t.src=e})},e.resize=function(t,e,r,n){if(!r&&!n)return{currentWidth:t,currentHeight:e};var i=t/e,o=void 0,a=void 0;return i>r/n?a=(o=Math.min(t,r))/i:o=(a=Math.min(e,n))*i,{width:o,height:a}}},function(t,e,r){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.base64ToFile=function(t){for(var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"image/jpeg",r=window.atob(t),n=[],i=0;i<r.length;i++)n[i]=r.charCodeAt(i);return new window.Blob([new Uint8Array(n)],{type:e})},e.imageToCanvas=function(t,e,r,n){var i=document.createElement("canvas"),o=i.getContext("2d");if(i.width=e,i.height=r,!n||n>8)return o.drawImage(t,0,0,i.width,i.height),i;switch(n>4&&(i.width=r,i.height=e),n){case 2:o.translate(e,0),o.scale(-1,1);break;case 3:o.translate(e,r),o.rotate(Math.PI);break;case 4:o.translate(0,r),o.scale(1,-1);break;case 5:o.rotate(.5*Math.PI),o.scale(1,-1);break;case 6:o.rotate(.5*Math.PI),o.translate(0,-r);break;case 7:o.rotate(.5*Math.PI),o.translate(e,-r),o.scale(-1,1);break;case 8:o.rotate(-.5*Math.PI),o.translate(-e,0)}return n>4?o.drawImage(t,0,0,i.height,i.width):o.drawImage(t,0,0,i.width,i.height),i},e.canvasToBlob=function(t,e){return new Promise(function(r,n){t.toBlob(function(t){r(t)},"image/jpeg",e)})},e.size=function(t){return{kB:.001*t,MB:1e-6*t}},e.blobToBase64=function(t){return new Promise(function(e,r){var n=new window.FileReader;n.addEventListener("load",function(t){e(t.target.result)},!1),n.addEventListener("error",function(t){r(t)},!1),n.readAsDataURL(t)})}},function(t,e,r){t.exports=r(6)},function(t,e,r){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.extractOrientation=function(t){return new Promise(function(e,r){var n=new window.FileReader;n.onload=function(t){var r=new DataView(t.target.result);65496!==r.getUint16(0,!1)&&e(-2);for(var n=r.byteLength,i=2;i<n;){var o=r.getUint16(i,!1);if(i+=2,65505===o){1165519206!==r.getUint32(i+=2,!1)&&e(-1);var a=18761===r.getUint16(i+=6,!1);i+=r.getUint32(i+4,a);var u=r.getUint16(i,a);i+=2;for(var s=0;s<u;s++)274===r.getUint16(i+12*s,a)&&e(r.getUint16(i+12*s+8,a))}else{if(65280!=(65280&o))break;i+=r.getUint16(i,!1)}}e(-1)},n.readAsArrayBuffer(t.slice(0,65536))})}},function(t,e,r){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n,i=r(2),o=(n=i)&&n.__esModule?n:{default:n},a=function(){function t(t,e){for(var r=0;r<e.length;r++){var n=e[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,n.key,n)}}return function(e,r,n){return r&&t(e.prototype,r),n&&t(e,n),e}}(),u=r(3),s=r(0),c=r(1);function f(t){return function(){var e=t.apply(this,arguments);return new Promise(function(t,r){return function n(i,o){try{var a=e[i](o),u=a.value}catch(t){return void r(t)}if(!a.done)return Promise.resolve(u).then(function(t){n("next",t)},function(t){n("throw",t)});t(u)}("next")})}}var l=function(){function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.data=e,this.name=e.name,this.type=e.type,this.size=e.size}return a(t,[{key:"setData",value:function(t){this.data=t,this.size=t.size,this.type=t.type}},{key:"_calculateOrientation",value:function(){var t=f(o.default.mark(function t(){var e;return o.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,(0,u.extractOrientation)(this.data);case 2:e=t.sent,this.orientation=e;case 4:case"end":return t.stop()}},t,this)}));return function(){return t.apply(this,arguments)}}()},{key:"load",value:function(){var t=f(o.default.mark(function t(){var e,r;return o.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,this._calculateOrientation();case 2:return e=URL.createObjectURL(this.data),r=new window.Image,t.next=6,(0,s.loadImageElement)(r,e);case 6:URL.revokeObjectURL(e),this._img=r,this.width=r.naturalWidth,this.height=r.naturalHeight;case 10:case"end":return t.stop()}},t,this)}));return function(){return t.apply(this,arguments)}}()},{key:"getCanvas",value:function(t,e,r){return void 0!==r?(0,c.imageToCanvas)(this._img,t,e,r):(0,c.imageToCanvas)(this._img,t,e,this.orientation)}}]),t}();e.default=l,t.exports=e.default},function(t,e){!function(e){"use strict";var r,n=Object.prototype,i=n.hasOwnProperty,o="function"==typeof Symbol?Symbol:{},a=o.iterator||"@@iterator",u=o.asyncIterator||"@@asyncIterator",s=o.toStringTag||"@@toStringTag",c="object"==typeof t,f=e.regeneratorRuntime;if(f)c&&(t.exports=f);else{(f=e.regeneratorRuntime=c?t.exports:{}).wrap=w;var l="suspendedStart",h="suspendedYield",p="executing",d="completed",A={},v={};v[a]=function(){return this};var y=Object.getPrototypeOf,g=y&&y(y(z([])));g&&g!==n&&i.call(g,a)&&(v=g);var m=B.prototype=x.prototype=Object.create(v);b.prototype=m.constructor=B,B.constructor=b,B[s]=b.displayName="GeneratorFunction",f.isGeneratorFunction=function(t){var e="function"==typeof t&&t.constructor;return!!e&&(e===b||"GeneratorFunction"===(e.displayName||e.name))},f.mark=function(t){return Object.setPrototypeOf?Object.setPrototypeOf(t,B):(t.__proto__=B,s in t||(t[s]="GeneratorFunction")),t.prototype=Object.create(m),t},f.awrap=function(t){return{__await:t}},Q(_.prototype),_.prototype[u]=function(){return this},f.AsyncIterator=_,f.async=function(t,e,r,n){var i=new _(w(t,e,r,n));return f.isGeneratorFunction(e)?i:i.next().then(function(t){return t.done?t.value:i.next()})},Q(m),m[s]="Generator",m[a]=function(){return this},m.toString=function(){return"[object Generator]"},f.keys=function(t){var e=[];for(var r in t)e.push(r);return e.reverse(),function r(){for(;e.length;){var n=e.pop();if(n in t)return r.value=n,r.done=!1,r}return r.done=!0,r}},f.values=z,O.prototype={constructor:O,reset:function(t){if(this.prev=0,this.next=0,this.sent=this._sent=r,this.done=!1,this.delegate=null,this.method="next",this.arg=r,this.tryEntries.forEach(P),!t)for(var e in this)"t"===e.charAt(0)&&i.call(this,e)&&!isNaN(+e.slice(1))&&(this[e]=r)},stop:function(){this.done=!0;var t=this.tryEntries[0].completion;if("throw"===t.type)throw t.arg;return this.rval},dispatchException:function(t){if(this.done)throw t;var e=this;function n(n,i){return u.type="throw",u.arg=t,e.next=n,i&&(e.method="next",e.arg=r),!!i}for(var o=this.tryEntries.length-1;o>=0;--o){var a=this.tryEntries[o],u=a.completion;if("root"===a.tryLoc)return n("end");if(a.tryLoc<=this.prev){var s=i.call(a,"catchLoc"),c=i.call(a,"finallyLoc");if(s&&c){if(this.prev<a.catchLoc)return n(a.catchLoc,!0);if(this.prev<a.finallyLoc)return n(a.finallyLoc)}else if(s){if(this.prev<a.catchLoc)return n(a.catchLoc,!0)}else{if(!c)throw new Error("try statement without catch or finally");if(this.prev<a.finallyLoc)return n(a.finallyLoc)}}}},abrupt:function(t,e){for(var r=this.tryEntries.length-1;r>=0;--r){var n=this.tryEntries[r];if(n.tryLoc<=this.prev&&i.call(n,"finallyLoc")&&this.prev<n.finallyLoc){var o=n;break}}o&&("break"===t||"continue"===t)&&o.tryLoc<=e&&e<=o.finallyLoc&&(o=null);var a=o?o.completion:{};return a.type=t,a.arg=e,o?(this.method="next",this.next=o.finallyLoc,A):this.complete(a)},complete:function(t,e){if("throw"===t.type)throw t.arg;return"break"===t.type||"continue"===t.type?this.next=t.arg:"return"===t.type?(this.rval=this.arg=t.arg,this.method="return",this.next="end"):"normal"===t.type&&e&&(this.next=e),A},finish:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.finallyLoc===t)return this.complete(r.completion,r.afterLoc),P(r),A}},catch:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.tryLoc===t){var n=r.completion;if("throw"===n.type){var i=n.arg;P(r)}return i}}throw new Error("illegal catch attempt")},delegateYield:function(t,e,n){return this.delegate={iterator:z(t),resultName:e,nextLoc:n},"next"===this.method&&(this.arg=r),A}}}function w(t,e,r,n){var i=e&&e.prototype instanceof x?e:x,o=Object.create(i.prototype),a=new O(n||[]);return o._invoke=function(t,e,r){var n=l;return function(i,o){if(n===p)throw new Error("Generator is already running");if(n===d){if("throw"===i)throw o;return j()}for(r.method=i,r.arg=o;;){var a=r.delegate;if(a){var u=k(a,r);if(u){if(u===A)continue;return u}}if("next"===r.method)r.sent=r._sent=r.arg;else if("throw"===r.method){if(n===l)throw n=d,r.arg;r.dispatchException(r.arg)}else"return"===r.method&&r.abrupt("return",r.arg);n=p;var s=E(t,e,r);if("normal"===s.type){if(n=r.done?d:h,s.arg===A)continue;return{value:s.arg,done:r.done}}"throw"===s.type&&(n=d,r.method="throw",r.arg=s.arg)}}}(t,r,a),o}function E(t,e,r){try{return{type:"normal",arg:t.call(e,r)}}catch(t){return{type:"throw",arg:t}}}function x(){}function b(){}function B(){}function Q(t){["next","throw","return"].forEach(function(e){t[e]=function(t){return this._invoke(e,t)}})}function _(t){var e;this._invoke=function(r,n){function o(){return new Promise(function(e,o){!function e(r,n,o,a){var u=E(t[r],t,n);if("throw"!==u.type){var s=u.arg,c=s.value;return c&&"object"==typeof c&&i.call(c,"__await")?Promise.resolve(c.__await).then(function(t){e("next",t,o,a)},function(t){e("throw",t,o,a)}):Promise.resolve(c).then(function(t){s.value=t,o(s)},a)}a(u.arg)}(r,n,e,o)})}return e=e?e.then(o,o):o()}}function k(t,e){var n=t.iterator[e.method];if(n===r){if(e.delegate=null,"throw"===e.method){if(t.iterator.return&&(e.method="return",e.arg=r,k(t,e),"throw"===e.method))return A;e.method="throw",e.arg=new TypeError("The iterator does not provide a 'throw' method")}return A}var i=E(n,t.iterator,e.arg);if("throw"===i.type)return e.method="throw",e.arg=i.arg,e.delegate=null,A;var o=i.arg;return o?o.done?(e[t.resultName]=o.value,e.next=t.nextLoc,"return"!==e.method&&(e.method="next",e.arg=r),e.delegate=null,A):o:(e.method="throw",e.arg=new TypeError("iterator result is not an object"),e.delegate=null,A)}function L(t){var e={tryLoc:t[0]};1 in t&&(e.catchLoc=t[1]),2 in t&&(e.finallyLoc=t[2],e.afterLoc=t[3]),this.tryEntries.push(e)}function P(t){var e=t.completion||{};e.type="normal",delete e.arg,t.completion=e}function O(t){this.tryEntries=[{tryLoc:"root"}],t.forEach(L,this),this.reset(!0)}function z(t){if(t){var e=t[a];if(e)return e.call(t);if("function"==typeof t.next)return t;if(!isNaN(t.length)){var n=-1,o=function e(){for(;++n<t.length;)if(i.call(t,n))return e.value=t[n],e.done=!1,e;return e.value=r,e.done=!0,e};return o.next=o}}return{next:j}}function j(){return{value:r,done:!0}}}(function(){return this}()||Function("return this")())},function(t,e,r){var n=function(){return this}()||Function("return this")(),i=n.regeneratorRuntime&&Object.getOwnPropertyNames(n).indexOf("regeneratorRuntime")>=0,o=i&&n.regeneratorRuntime;if(n.regeneratorRuntime=void 0,t.exports=r(5),i)n.regeneratorRuntime=o;else try{delete n.regeneratorRuntime}catch(t){n.regeneratorRuntime=void 0}},function(t,e,r){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=s(r(2)),i=function(){function t(t,e){for(var r=0;r<e.length;r++){var n=e[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,n.key,n)}}return function(e,r,n){return r&&t(e.prototype,r),n&&t(e,n),e}}(),o=function(t){if(t&&t.__esModule)return t;var e={};if(null!=t)for(var r in t)Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e.default=t,e}(r(1)),a=r(0),u=s(r(4));function s(t){return t&&t.__esModule?t:{default:t}}function c(t){return function(){var e=t.apply(this,arguments);return new Promise(function(t,r){return function n(i,o){try{var a=e[i](o),u=a.value}catch(t){return void r(t)}if(!a.done)return Promise.resolve(u).then(function(t){n("next",t)},function(t){n("throw",t)});t(u)}("next")})}}var f=function(){function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.setOptions(e)}return i(t,[{key:"setOptions",value:function(t){var e={targetSize:1/0,quality:.75,minQuality:.5,qualityStepSize:.1,maxWidth:1920,maxHeight:1920,resize:!0,throwIfSizeNotReached:!1,autoRotate:!0},r=new Proxy(t,{get:function(t,r){return r in t?t[r]:e[r]}});this.options=r}},{key:"_compressFile",value:function(){var t=c(n.default.mark(function t(e){var r,i;return n.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return r=new u.default(e),(i={}).start=window.performance.now(),i.quality=this.options.quality,i.startType=r.type,t.next=7,r.load();case 7:return t.next=9,this._compressImage(r,i);case 9:return t.abrupt("return",t.sent);case 10:case"end":return t.stop()}},t,this)}));return function(e){return t.apply(this,arguments)}}()},{key:"_compressImage",value:function(){var t=c(n.default.mark(function t(e,r){var i,u,s,c,f;return n.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return r.startWidth=e.width,r.startHeight=e.height,i=void 0,u=void 0,this.options.resize?(s=(0,a.resize)(e.width,e.height,this.options.maxWidth,this.options.maxHeight),i=s.width,u=s.height):(i=e.width,u=e.height),r.endWidth=i,r.endHeight=u,c=this.doAutoRotation?void 0:1,f=e.getCanvas(i,u,c),r.iterations=0,r.startSizeMB=o.size(e.size).MB,t.next=12,this._loopCompression(f,e,r);case 12:return r.endSizeMB=o.size(e.size).MB,r.sizeReducedInPercent=(r.startSizeMB-r.endSizeMB)/r.startSizeMB*100,r.end=window.performance.now(),r.elapsedTimeInSeconds=(r.end-r.start)/1e3,r.endType=e.type,t.abrupt("return",{photo:e,info:r});case 18:case"end":return t.stop()}},t,this)}));return function(e,r){return t.apply(this,arguments)}}()},{key:"_loopCompression",value:function(){var t=c(n.default.mark(function t(e,r,i){var a;return n.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return i.iterations++,t.t0=r,t.next=4,o.canvasToBlob(e,i.quality);case 4:if(t.t1=t.sent,t.t0.setData.call(t.t0,t.t1),1==i.iterations&&(r.width=i.endWidth,r.height=i.endHeight),!(o.size(r.size).MB>this.options.targetSize)){t.next=24;break}if(!(i.quality.toFixed(10)-.1<this.options.minQuality)){t.next=18;break}if(a="Couldn't compress image to target size while maintaining quality.\n        Target size: "+this.options.targetSize+"\n        Actual size: "+o.size(r.size).MB,this.options.throwIfSizeNotReached){t.next=14;break}console.error(a),t.next=15;break;case 14:throw new Error(a);case 15:return t.abrupt("return");case 18:return i.quality-=this.options.qualityStepSize,t.next=21,this._loopCompression(e,r,i);case 21:return t.abrupt("return",t.sent);case 22:t.next=25;break;case 24:return t.abrupt("return");case 25:case"end":return t.stop()}},t,this)}));return function(e,r,n){return t.apply(this,arguments)}}()},{key:"setAutoRotate",value:function(){var e=c(n.default.mark(function e(){var r;return n.default.wrap(function(e){for(;;)switch(e.prev=e.next){case 0:return e.next=2,t.automaticRotationFeatureTest();case 2:r=e.sent,this.doAutoRotation=this.options.autoRotate&&!r;case 4:case"end":return e.stop()}},e,this)}));return function(){return e.apply(this,arguments)}}()},{key:"compress",value:function(){var t=c(n.default.mark(function t(e){var r=this;return n.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,this.setAutoRotate();case 2:return t.abrupt("return",Promise.all(e.map(function(t){return r._compressFile(t)})));case 3:case"end":return t.stop()}},t,this)}));return function(e){return t.apply(this,arguments)}}()}],[{key:"blobToBase64",value:function(){var t=c(n.default.mark(function t(){var e=arguments;return n.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,o.blobToBase64.apply(o,e);case 2:return t.abrupt("return",t.sent);case 3:case"end":return t.stop()}},t,this)}));return function(){return t.apply(this,arguments)}}()},{key:"loadImageElement",value:function(){var t=c(n.default.mark(function t(){var e=arguments;return n.default.wrap(function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,a.loadImageElement.apply(void 0,e);case 2:return t.abrupt("return",t.sent);case 3:case"end":return t.stop()}},t,this)}));return function(){return t.apply(this,arguments)}}()},{key:"automaticRotationFeatureTest",value:function(){return new Promise(function(t){var e=new Image;e.onload=function(){var r=1===e.width&&2===e.height;t(r)},e.src="data:image/jpeg;base64,/9j/4QAiRXhpZgAATU0AKgAAAAgAAQESAAMAAAABAAYAAAAAAAD/2wCEAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/AABEIAAEAAgMBEQACEQEDEQH/xABKAAEAAAAAAAAAAAAAAAAAAAALEAEAAAAAAAAAAAAAAAAAAAAAAQEAAAAAAAAAAAAAAAAAAAAAEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8H//2Q=="})}}]),t}();e.default=f,t.exports=e.default}])});
