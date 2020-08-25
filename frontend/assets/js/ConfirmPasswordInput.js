"use strict";
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
exports.__esModule = true;
var PasswordInput_1 = require("./PasswordInput");
var ConfirmPasswordInput = /** @class */ (function (_super) {
    __extends(ConfirmPasswordInput, _super);
    function ConfirmPasswordInput(identifier, secondIdentifier, name, secondName, minimumSize, maximumSize, allowedCharacters, element, secondElement) {
        if (element === void 0) { element = null; }
        if (secondElement === void 0) { secondElement = null; }
        var _this = _super.call(this, identifier, name, minimumSize, maximumSize, allowedCharacters, element) || this;
        _this.secondIdentifier = secondIdentifier;
        _this.secondName = secondName;
        _this.secondElement = secondElement;
        return _this;
    }
    // Getters and setters
    ConfirmPasswordInput.prototype.getSecondIdentifier = function () {
        return this.secondIdentifier;
    };
    ConfirmPasswordInput.prototype.getSecondName = function () {
        return this.secondName;
    };
    ConfirmPasswordInput.prototype.getSecondElement = function () {
        return this.secondElement;
    };
    ConfirmPasswordInput.prototype.setSecondElement = function (secondElement) {
        this.secondElement = secondElement;
    };
    return ConfirmPasswordInput;
}(PasswordInput_1.PasswordInput));
exports.ConfirmPasswordInput = ConfirmPasswordInput;
