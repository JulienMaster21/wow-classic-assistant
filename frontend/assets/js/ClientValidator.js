"use strict";
exports.__esModule = true;
exports.ClientValidator = void 0;
var TextInput_1 = require("./TextInput");
var $ = require("jquery");
var Form_1 = require("./Form");
var EmailInput_1 = require("./EmailInput");
var PasswordInput_1 = require("./PasswordInput");
var ConfirmPasswordInput_1 = require("./ConfirmPasswordInput");
var ClientValidator = /** @class */ (function () {
    function ClientValidator() {
        var _this = this;
        // Initialise arrays
        this.allPossibleInputs = [];
        this.forms = [];
        // Get all possible inputs
        this.createPossibleInputs();
        // Create array of all input identifiers and names
        var allIdentifiers = [];
        this.allPossibleInputs.forEach(function (input) {
            allIdentifiers.push(input.getIdentifier());
        });
        // Get all forms on the page
        var formElements = $('form').get();
        formElements.forEach(function (element) {
            _this.forms.push(new Form_1.Form(element, allIdentifiers, _this.allPossibleInputs));
        });
    }
    ClientValidator.prototype.createPossibleInputs = function () {
        // Name
        this.getAllPossibleInputs().push(new TextInput_1.TextInput('username', 'Username', 1, 255, new RegExp('[a-zA-Z0-9]')));
        // Email
        this.getAllPossibleInputs().push(new EmailInput_1.EmailInput('email', 'Email', 1, 255, new RegExp('[a-zA-Z0-9]')));
        // Password
        this.getAllPossibleInputs().push(new PasswordInput_1.PasswordInput('password', 'Password', 10, 255, new RegExp('[a-zA-Z0-9]')));
        // Register Password
        this.getAllPossibleInputs().push(new ConfirmPasswordInput_1.ConfirmPasswordInput('passwordfirst', 'passwordsecond', 'Password', 'Confirm Password', 10, 255, new RegExp('[a-zA-Z0-9]')));
    };
    // Getters and setters
    ClientValidator.prototype.getAllPossibleInputs = function () {
        return this.allPossibleInputs;
    };
    ClientValidator.prototype.setAllPossibleInputs = function (possibleInputs) {
        this.allPossibleInputs = possibleInputs;
    };
    ClientValidator.prototype.getForms = function () {
        return this.forms;
    };
    ClientValidator.prototype.setForms = function (forms) {
        this.forms = forms;
    };
    return ClientValidator;
}());
exports.ClientValidator = ClientValidator;
