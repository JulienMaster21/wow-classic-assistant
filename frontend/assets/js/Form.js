"use strict";
exports.__esModule = true;
var TextInput_1 = require("./TextInput");
var EmailInput_1 = require("./EmailInput");
var ConfirmPasswordInput_1 = require("./ConfirmPasswordInput");
var Form = /** @class */ (function () {
    function Form(element, allIdentifiers, allPossibleInputs) {
        var _this = this;
        // Initialise inputs array
        this.inputs = [];
        // Initialise validation passed as false
        this.validationPassed = false;
        // Initialise confirm password needs second input as false
        var confirmPasswordNeedsSecondInput = false;
        // Get inputs and submit button
        this.element = element;
        var formName = this.element.name;
        this.element.childNodes.forEach(function (formGroup) {
            // Check if element is a div and has children
            if (formGroup.nodeName === 'DIV' &&
                formGroup.childNodes.length > 0) {
                console.log(formGroup);
                formGroup.childNodes.forEach(function (child) {
                    if (child.nodeName === 'INPUT') {
                        var input = child;
                        var inputIdentifier = (input.name
                            .replace(new RegExp('[\\[\\]]', 'g'), ''))
                            .slice(formName.length);
                        var index = allIdentifiers.indexOf(inputIdentifier);
                        if (confirmPasswordNeedsSecondInput) {
                            var confirmPasswordInput = _this.inputs[_this.inputs.length - 1];
                            confirmPasswordInput.setSecondElement(input);
                            confirmPasswordNeedsSecondInput = false;
                        }
                        if (index !== -1) {
                            if (input.type === 'text') {
                                var textInput = allPossibleInputs[index];
                                _this.inputs.push(new TextInput_1.TextInput(textInput.getIdentifier(), textInput.getName(), textInput.getMinimumSize(), textInput.getMaximumSize(), textInput.getAllowedCharacters(), input));
                            }
                            if (input.type === 'password') {
                                if (allPossibleInputs[index] instanceof ConfirmPasswordInput_1.ConfirmPasswordInput) {
                                    var confirmPasswordInput = allPossibleInputs[index];
                                    _this.inputs.push(new ConfirmPasswordInput_1.ConfirmPasswordInput(confirmPasswordInput.getIdentifier(), confirmPasswordInput.getSecondIdentifier(), confirmPasswordInput.getName(), confirmPasswordInput.getSecondName(), confirmPasswordInput.getMinimumSize(), confirmPasswordInput.getMaximumSize(), confirmPasswordInput.getAllowedCharacters(), input));
                                    confirmPasswordNeedsSecondInput = true;
                                }
                                else {
                                    var passwordInput = allPossibleInputs[index];
                                    _this.inputs.push(new TextInput_1.TextInput(passwordInput.getIdentifier(), passwordInput.getName(), passwordInput.getMinimumSize(), passwordInput.getMaximumSize(), passwordInput.getAllowedCharacters(), input));
                                }
                            }
                            if (input.type === 'email') {
                                var emailInput = allPossibleInputs[index];
                                _this.inputs.push(new EmailInput_1.EmailInput(emailInput.getIdentifier(), emailInput.getName(), emailInput.getMinimumSize(), emailInput.getMaximumSize(), emailInput.getAllowedCharacters(), input));
                            }
                        }
                    }
                    else if (child.nodeName === 'BUTTON') {
                        _this.submitButton = child;
                    }
                });
            }
        });
        // Set on submit event handler on form element
        this.element.addEventListener('submit', function (event) {
            // Check all inputs
            _this.inputs.forEach(function (input) {
                input.checkValidation(input.getElement());
            });
            _this.checkValidation();
            // If validation didn't pass then prevent submission
            if (!_this.validationPassed) {
                event.preventDefault();
            }
        });
        // Set on blur event handler on inputs
        this.inputs.forEach(function (input) {
            input.getElement().addEventListener('blur', function () {
                input.checkValidation(input.getElement());
                _this.checkValidation();
            });
        });
        // If form has no inputs to check then pass validation
        if (this.inputs.length === 0) {
            this.validationPassed = true;
        }
    }
    Form.prototype.checkValidation = function () {
        var validationPassed = true;
        // check if inputs pass validation
        this.inputs.forEach(function (input) {
            if (!input.getValidationPassed()) {
                validationPassed = false;
            }
        });
        this.validationPassed = validationPassed;
    };
    // Getters and setters
    Form.prototype.getElement = function () {
        return this.element;
    };
    Form.prototype.setElement = function (element) {
        this.element = element;
    };
    Form.prototype.getInputs = function () {
        return this.inputs;
    };
    Form.prototype.setInputs = function (inputs) {
        this.inputs = inputs;
    };
    Form.prototype.getSubmitButton = function () {
        return this.submitButton;
    };
    Form.prototype.setSubmitButton = function (button) {
        this.submitButton = button;
    };
    Form.prototype.getValidationPassed = function () {
        return this.validationPassed;
    };
    Form.prototype.setValidationPassed = function (validationPassed) {
        this.validationPassed = validationPassed;
    };
    return Form;
}());
exports.Form = Form;
