/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
var Selectors,__decorate=function(t,e,o,r){var n,l=arguments.length,a=l<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(t,e,o,r);else for(var s=t.length-1;s>=0;s--)(n=t[s])&&(a=(l<3?n(a):l>3?n(e,o,a):n(e,o))||a);return l>3&&a&&Object.defineProperty(e,o,a),a};import{html,css,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import Severity from"@typo3/backend/severity.js";import Modal from"@typo3/backend/modal.js";import{lll}from"@typo3/core/lit-helper.js";!function(t){t.formatSelector=".t3js-record-download-format-selector",t.formatOptions=".t3js-record-download-format-option"}(Selectors||(Selectors={}));let RecordDownloadButton=class extends LitElement{constructor(){super(),this.addEventListener("click",(t=>{t.preventDefault(),this.showDownloadConfigurationModal()})),this.addEventListener("keydown",(t=>{"Enter"!==t.key&&" "!==t.key||(t.preventDefault(),this.showDownloadConfigurationModal())}))}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return html`<slot></slot>`}showDownloadConfigurationModal(){if(!this.url)return;const t=Modal.advanced({content:this.url,title:this.title||"Download records",severity:SeverityEnum.notice,size:Modal.sizes.small,type:Modal.types.ajax,buttons:[{text:this.close||lll("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>t.hideModal()},{text:this.ok||lll("button.ok")||"Download",btnClass:"btn-"+Severity.getCssClass(SeverityEnum.info),name:"download",trigger:()=>{const e=t.querySelector("form");e&&e.submit(),t.hideModal()}}],ajaxCallback:()=>{const e=t.querySelector(Selectors.formatSelector),o=t.querySelectorAll(Selectors.formatOptions);null!==e&&o.length&&e.addEventListener("change",(t=>{const e=t.target.value;o.forEach((t=>{t.dataset.formatname!==e?t.classList.add("hide"):t.classList.remove("hide")}))}))}})}};RecordDownloadButton.styles=[css`:host { cursor: pointer; appearance: button; }`],__decorate([property({type:String})],RecordDownloadButton.prototype,"url",void 0),__decorate([property({type:String})],RecordDownloadButton.prototype,"title",void 0),__decorate([property({type:String})],RecordDownloadButton.prototype,"ok",void 0),__decorate([property({type:String})],RecordDownloadButton.prototype,"close",void 0),RecordDownloadButton=__decorate([customElement("typo3-recordlist-record-download-button")],RecordDownloadButton);export{RecordDownloadButton};