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
import{lll}from"@typo3/core/lit-helper.js";import Modal from"@typo3/backend/modal.js";import"@typo3/backend/element/icon-element.js";import"@typo3/backend/input/clearable.js";import"@typo3/backend/live-search/element/result/result-pagination.js";import"@typo3/backend/live-search/element/search-option-item.js";import"@typo3/backend/live-search/live-search-shortcut.js";import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import BrowserSession from"@typo3/backend/storage/browser-session.js";import{componentName as resultContainerComponentName}from"@typo3/backend/live-search/element/result/result-container.js";var Identifiers;!function(e){e.toolbarItem=".t3js-topbar-button-search",e.searchOptionDropdownToggle=".t3js-search-provider-dropdown-toggle"}(Identifiers||(Identifiers={}));class LiveSearch{constructor(){this.search=async e=>{if(""===e.get("query").toString())this.updateSearchResults(null);else{document.querySelector(resultContainerComponentName).loading=!0;const t=await(await new AjaxRequest(TYPO3.settings.ajaxUrls.livesearch).post(e)).raw().json();this.updateSearchResults(t)}},DocumentService.ready().then((()=>{this.registerEvents()}))}registerEvents(){new RegularEvent("click",(()=>{this.openSearchModal()})).delegateTo(document,Identifiers.toolbarItem),new RegularEvent("typo3:live-search:trigger-open",(()=>{Modal.currentModal||this.openSearchModal()})).bindTo(document)}openSearchModal(){const e=new URL(TYPO3.settings.ajaxUrls.livesearch_form,window.location.origin);e.searchParams.set("query",BrowserSession.get("livesearch-term")??""),e.searchParams.set("offset",BrowserSession.get("livesearch-offset")??"0");const t=Object.entries(BrowserSession.getByPrefix("livesearch-option-")).filter((e=>"1"===e[1])).map((e=>{const t=e[0].replace("livesearch-option-",""),[o,r]=t.split("-",2);return{key:o,value:r}})),o=this.composeSearchOptions(t);for(const[t,r]of Object.entries(o))for(const o of r)e.searchParams.append(`${t}[]`,o);const r=Modal.advanced({type:Modal.types.ajax,content:e.toString(),title:lll("labels.search"),severity:SeverityEnum.notice,size:Modal.sizes.medium,ajaxCallback:()=>{const e=r.querySelector("typo3-backend-live-search"),t=e.querySelector("form"),o=t.querySelector('input[type="search"]'),n=t.querySelector('input[name="offset"]');new RegularEvent("livesearch:demand-changed",(()=>{n.value="0"})).bindTo(e),new RegularEvent("livesearch:pagination-selected",(e=>{n.value=e.detail.offset,t.requestSubmit()})).bindTo(e),new RegularEvent("submit",(e=>{e.preventDefault();const o=new FormData(t);this.search(o).then((()=>{const e=o.get("query").toString(),t=o.get("offset")?.toString();BrowserSession.set("livesearch-term",e),t&&BrowserSession.set("livesearch-offset",t)}));const r=t.querySelector("[data-active-options-counter]"),n=parseInt(r.dataset.activeOptionsCounter,10);r.querySelector("output").textContent=n.toString(10),r.classList.toggle("hidden",0===n)})).bindTo(t),o.clearable({onClear:()=>{t.requestSubmit()}});const a=document.querySelector("typo3-backend-live-search-result-container");new RegularEvent("live-search:item-chosen",(()=>{Modal.dismiss()})).bindTo(a),new RegularEvent("typo3:live-search:option-invoked",(o=>{e.dispatchEvent(new CustomEvent("livesearch:demand-changed"));const r=t.querySelector("[data-active-options-counter]");let n=parseInt(r.dataset.activeOptionsCounter,10);n=o.detail.active?n+1:n-1,r.dataset.activeOptionsCounter=n.toString(10)})).bindTo(e),new RegularEvent("hide.bs.dropdown",(()=>{t.requestSubmit()})).bindTo(r.querySelector(Identifiers.searchOptionDropdownToggle)),new DebounceEvent("input",(()=>{e.dispatchEvent(new CustomEvent("livesearch:demand-changed")),t.requestSubmit()})).bindTo(o),new RegularEvent("keydown",this.handleKeyDown).bindTo(o),t.requestSubmit()}});["modal-loaded","typo3-modal-shown"].forEach((e=>{r.addEventListener(e,(()=>{const e=r.querySelector('input[type="search"]');null!==e&&(e.focus(),e.select())}))}))}composeSearchOptions(e){const t={};return e.forEach((e=>{void 0===t[e.key]&&(t[e.key]=[]),t[e.key].push(e.value)})),t}handleKeyDown(e){if("ArrowDown"!==e.key)return;e.preventDefault();const t=document.querySelector("typo3-backend-live-search").querySelector("typo3-backend-live-search-result-item");t?.focus()}updateSearchResults(e){const t=document.querySelector("typo3-backend-live-search-result-container");t.results=e?.results??null,t.loading=!1,this.updatePagination(e?.pagination??null)}updatePagination(e){document.querySelector("typo3-backend-live-search-result-pagination").pagination=e}}export default top.TYPO3.LiveSearch??new LiveSearch;