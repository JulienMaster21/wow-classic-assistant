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
var Input_1 = require("./Input");
var TextInput = /** @class */ (function (_super) {
    __extends(TextInput, _super);
    function TextInput(identifier, name, minimumSize, maximumSize, allowedCharacters, element) {
        if (element === void 0) { element = null; }
        var _this = _super.call(this, identifier, name, element) || this;
        _this.minimumSize = minimumSize;
        _this.maximumSize = maximumSize;
        _this.allowedCharacters = allowedCharacters;
        return _this;
    }
    TextInput.prototype.checkValidation = function (element) {
        var _this = this;
        // Initialise local variables
        var validationPassed = true;
        var messages = [];
        // Perform all checks
        // Check size
        var currentLength = element.value.length;
        var sizeMessage = this.checkSize(currentLength);
        if (sizeMessage !== null) {
            messages.push(sizeMessage);
            validationPassed = false;
        }
        // Check allowed characters
        var allowedCharacterMessage = this.checkAllowedCharacters(element.value);
        if (allowedCharacterMessage !== null) {
            messages.push(allowedCharacterMessage);
            validationPassed = false;
        }
        // Check if validation passed
        if (validationPassed) {
            this.setValidationPassed(true);
        }
        else {
            this.setValidationPassed(false);
        }
        // Remove messages
        this.getElement().parentNode.childNodes.forEach(function (child) {
            if (child.nodeName === 'P') {
                var paragraph = child;
                if (paragraph.classList.contains('error')) {
                    paragraph.remove();
                }
            }
        });
        // Apply styling and messages
        messages.forEach(function (message) {
            var messageElement = document.createElement('p');
            messageElement.textContent = message;
            messageElement.classList.add('error');
            _this.getElement().parentNode.insertBefore(messageElement, _this.getElement());
        });
        // On success
        if (this.getValidationPassed()) {
            this.getElement().classList.remove('error');
            this.getElement().classList.add('success');
        }
        // On error
        else {
            this.getElement().classList.remove('success');
            this.getElement().classList.add('error');
        }
    };
    TextInput.prototype.checkSize = function (currentLength) {
        // Initialise error messages
        var minimumSizeMessage = 'The ' + this.getName() + ' field doesn\'t contain enough characters.';
        var maximumSizeMessage = 'The ' + this.getName() + ' field contains too many characters.';
        // Check size length
        if (currentLength < this.minimumSize) {
            return minimumSizeMessage;
        }
        else if (currentLength > this.maximumSize) {
            return maximumSizeMessage;
        }
        else {
            return null;
        }
    };
    TextInput.prototype.checkAllowedCharacters = function (inputValue) {
        var _this = this;
        // Get all invalid characters
        var invalidCharacters = [];
        inputValue.split('').forEach(function (character) {
            if (!_this.allowedCharacters.test(character) && invalidCharacters.indexOf(character) === -1) {
                invalidCharacters.push(character);
            }
        });
        // Check if error message should be displayed
        if (invalidCharacters.length > 0) {
            return 'The following characters are invalid: ' + invalidCharacters.join(', ') + '.';
        }
        else {
            return null;
        }
    };
    // Getters and setters
    TextInput.prototype.getMinimumSize = function () {
        return this.minimumSize;
    };
    TextInput.prototype.getMaximumSize = function () {
        return this.maximumSize;
    };
    TextInput.prototype.getAllowedCharacters = function () {
        return this.allowedCharacters;
    };
    return TextInput;
}(Input_1.Input));
exports.TextInput = TextInput;
