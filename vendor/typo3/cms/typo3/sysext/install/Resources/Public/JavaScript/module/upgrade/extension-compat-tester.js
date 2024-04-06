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
import"bootstrap";import $ from"jquery";import{AbstractInteractableModule}from"@typo3/install/module/abstract-interactable-module.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import InfoBox from"@typo3/install/renderable/info-box.js";import ProgressBar from"@typo3/install/renderable/progress-bar.js";import Severity from"@typo3/install/renderable/severity.js";import Router from"@typo3/install/router.js";class ExtensionCompatTester extends AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-extensionCompatTester-check",this.selectorUninstallTrigger=".t3js-extensionCompatTester-uninstall",this.selectorOutputContainer=".t3js-extensionCompatTester-output"}initialize(e){this.currentModal=e,this.getLoadedExtensionList(),e.on("click",this.selectorCheckTrigger,(()=>{this.findInModal(this.selectorUninstallTrigger).addClass("hidden"),this.findInModal(this.selectorOutputContainer).empty(),this.getLoadedExtensionList()})),e.on("click",this.selectorUninstallTrigger,(e=>{this.uninstallExtension($(e.target).data("extension"))}))}getLoadedExtensionList(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=this.findInModal(this.selectorOutputContainer);if(t.length){const e=ProgressBar.render(Severity.loading,"Loading...","");t.append(e)}new AjaxRequest(Router.getUrl("extensionCompatTesterLoadedExtensionList")).get({cache:"no-cache"}).then((async t=>{const o=await t.resolve();e.empty().append(o.html),Modal.setButtons(o.buttons);const n=this.findInModal(this.selectorOutputContainer),s=ProgressBar.render(Severity.loading,"Loading...","");n.append(s),!0===o.success?this.loadExtLocalconf().then((()=>{n.append(InfoBox.render(Severity.ok,"ext_localconf.php of all loaded extensions successfully loaded","")),this.loadExtTables().then((()=>{n.append(InfoBox.render(Severity.ok,"ext_tables.php of all loaded extensions successfully loaded",""))}),(async e=>{this.renderFailureMessages("ext_tables.php",(await e.response.json()).brokenExtensions,n)})).finally((()=>{this.unlockModal()}))}),(async e=>{this.renderFailureMessages("ext_localconf.php",(await e.response.json()).brokenExtensions,n),n.append(InfoBox.render(Severity.notice,"Skipped scanning ext_tables.php files due to previous errors","")),this.unlockModal()})):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(t=>{Router.handleAjaxError(t,e)}))}unlockModal(){this.findInModal(this.selectorOutputContainer).find(".alert-loading").remove(),this.findInModal(this.selectorCheckTrigger).removeClass("disabled").prop("disabled",!1)}renderFailureMessages(e,t,o){for(const n of t){let t;n.isProtected||(t=$("<button />",{class:"btn btn-danger t3js-extensionCompatTester-uninstall"}).attr("data-extension",n.name).text('Uninstall extension "'+n.name+'"')),o.append(InfoBox.render(Severity.error,"Loading "+e+' of extension "'+n.name+'" failed',n.isProtected?"Extension is mandatory and cannot be uninstalled.":""),t)}this.unlockModal()}loadExtLocalconf(){const e=this.getModuleContent().data("extension-compat-tester-load-ext_localconf-token");return new AjaxRequest(Router.getUrl()).post({install:{action:"extensionCompatTesterLoadExtLocalconf",token:e}})}loadExtTables(){const e=this.getModuleContent().data("extension-compat-tester-load-ext_tables-token");return new AjaxRequest(Router.getUrl()).post({install:{action:"extensionCompatTesterLoadExtTables",token:e}})}uninstallExtension(e){const t=this.getModuleContent().data("extension-compat-tester-uninstall-extension-token"),o=this.getModalBody(),n=$(this.selectorOutputContainer),s=ProgressBar.render(Severity.loading,"Loading...","");n.append(s),new AjaxRequest(Router.getUrl()).post({install:{action:"extensionCompatTesterUninstallExtension",token:t,extension:e}}).then((async e=>{const t=await e.resolve();t.success?(Array.isArray(t.status)&&t.status.forEach((e=>{const t=InfoBox.render(e.severity,e.title,e.message);o.find(this.selectorOutputContainer).empty().append(t)})),this.findInModal(this.selectorUninstallTrigger).addClass("hidden"),this.getLoadedExtensionList()):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(e=>{Router.handleAjaxError(e,o)}))}}export default new ExtensionCompatTester;