export default(new function(){const t={};!function(t){var e=[];function s(s){var n=this,r="$BroadcastChannel$"+(s=String(s))+"$";e[r]=e[r]||[],e[r].push(this),this._name=s,this._id=r,this._closed=!1,this._mc=new MessageChannel,this._mc.port1.start(),this._mc.port2.start(),t.addEventListener("storage",(function(e){if(e.storageArea===t.localStorage&&null!=e.newValue&&""!==e.newValue&&e.key.substring(0,r.length)===r){var s=JSON.parse(e.newValue);n._mc.port2.postMessage(s)}}))}s.prototype={get name(){return this._name},postMessage:function(s){var n=this;if(this._closed){var r=new Error;throw r.name="InvalidStateError",r}var o=JSON.stringify(s),i=this._id+String(Date.now())+"$"+String(Math.random());t.localStorage.setItem(i,o),setTimeout((function(){t.localStorage.removeItem(i)}),500),e[this._id].forEach((function(t){t!==n&&t._mc.port2.postMessage(JSON.parse(o))}))},close:function(){if(!this._closed){this._closed=!0,this._mc.port1.close(),this._mc.port2.close();var t=e[this._id].indexOf(this);e[this._id].splice(t,1)}},get onmessage(){return this._mc.port1.onmessage},set onmessage(t){this._mc.port1.onmessage=t},addEventListener:function(){return this._mc.port1.addEventListener.apply(this._mc.port1,arguments)},removeEventListener:function(){return this._mc.port1.removeEventListener.apply(this._mc.port1,arguments)},dispatchEvent:function(){return this._mc.port1.dispatchEvent.apply(this._mc.port1,arguments)}},t.BroadcastChannel=t.BroadcastChannel||s}(self),this.__default_export=t}).__default_export;