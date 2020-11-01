"use strict";
exports.__esModule = true;
exports.Input = void 0;
var Input = /** @class */ (function () {
    function Input(identifier, name, element) {
        if (element === void 0) { element = null; }
        this.identifier = identifier;
        this.name = name;
        this.validationPassed = false;
        this.element = element;
    }
    // Getters and setters
    Input.prototype.getName = function () {
        return this.name;
    };
    Input.prototype.getIdentifier = function () {
        return this.identifier;
    };
    Input.prototype.getElement = function () {
        return this.element;
    };
    Input.prototype.setElement = function (element) {
        this.element = element;
    };
    Input.prototype.getValidationPassed = function () {
        return this.validationPassed;
    };
    Input.prototype.setValidationPassed = function (validationPassed) {
        this.validationPassed = validationPassed;
    };
    return Input;
}());
exports.Input = Input;
