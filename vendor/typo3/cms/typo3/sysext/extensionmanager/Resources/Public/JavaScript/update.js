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
import $ from"jquery";import NProgress from"nprogress";import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import RegularEvent from"@typo3/core/event/regular-event.js";var ExtensionManagerUpdateIdentifier;!function(e){e.extensionTable="#terTable",e.terUpdateAction=".update-from-ter",e.pagination=".pagination-wrap",e.splashscreen=".splash-receivedata",e.terTableWrapper="#terTableWrapper .table"}(ExtensionManagerUpdateIdentifier||(ExtensionManagerUpdateIdentifier={}));class ExtensionManagerUpdate{initializeEvents(){const e=document.querySelector(ExtensionManagerUpdateIdentifier.terUpdateAction);null!==e&&(new RegularEvent("submit",(e=>{e.preventDefault(),this.updateFromTer(e.target.action,!0)})).bindTo(e),this.updateFromTer(e.action,!1))}updateFromTer(e,t){t&&(e+="&forceUpdateCheck=1"),$(ExtensionManagerUpdateIdentifier.terUpdateAction).addClass("extensionmanager-is-hidden"),$(ExtensionManagerUpdateIdentifier.extensionTable).hide(),$(ExtensionManagerUpdateIdentifier.splashscreen).addClass("extensionmanager-is-shown"),$(ExtensionManagerUpdateIdentifier.terTableWrapper).addClass("extensionmanager-is-loading"),$(ExtensionManagerUpdateIdentifier.pagination).addClass("extensionmanager-is-loading");let a=!1;NProgress.start(),new AjaxRequest(e).get().then((async e=>{const t=await e.resolve();t.errorMessage.length&&Notification.error(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],t.errorMessage,10);const n=$(ExtensionManagerUpdateIdentifier.terUpdateAction+" .extension-list-last-updated");n.text(t.timeSinceLastUpdate),n.attr("title",TYPO3.lang["extensionList.updateFromTer.lastUpdate.timeOfLastUpdate"]+t.lastUpdateTime),t.updated&&(a=!0,window.location.replace(window.location.href))}),(async e=>{const t=e.response.statusText+"("+e.response.status+"): "+await e.response.text();Notification.warning(TYPO3.lang["extensionList.updateFromTerFlashMessage.title"],t,10)})).finally((()=>{NProgress.done(),a||($(ExtensionManagerUpdateIdentifier.splashscreen).removeClass("extensionmanager-is-shown"),$(ExtensionManagerUpdateIdentifier.terTableWrapper).removeClass("extensionmanager-is-loading"),$(ExtensionManagerUpdateIdentifier.pagination).removeClass("extensionmanager-is-loading"),$(ExtensionManagerUpdateIdentifier.terUpdateAction).removeClass("extensionmanager-is-hidden"),$(ExtensionManagerUpdateIdentifier.extensionTable).show())}))}}export default ExtensionManagerUpdate;