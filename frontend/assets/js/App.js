"use strict";
exports.__esModule = true;
var ClientValidator_1 = require("./ClientValidator");
var $ = require("jquery");
require('bootstrap');
require('@fortawesome/fontawesome-free/css/all.min.css');
require('@fortawesome/fontawesome-free/js/all.js');
var App = /** @class */ (function () {
    function App() {
        // Enable popover
        $(function () {
            // @ts-ignore
            $('[data-toggle="popover"]').popover();
        });
        // Initialise client validation
        this.clientValidator = new ClientValidator_1.ClientValidator();
        // Initialise navigation handler as null
        this.navigationHandler = null;
    }
    App.prototype.removeFlashMessage = function (closeButton) {
        closeButton.parentElement.remove();
    };
    // Getters and setters
    App.prototype.getClientValidator = function () {
        return this.clientValidator;
    };
    App.prototype.getNavigationHandler = function () {
        return this.navigationHandler;
    };
    App.prototype.setNavigationHandler = function (navigationHandler) {
        this.navigationHandler = navigationHandler;
    };
    return App;
}());
exports.App = App;
exports.app = new App();
// @ts-ignore
window.app = exports.app;
