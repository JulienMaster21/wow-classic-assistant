"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
exports.__esModule = true;
exports.DatabaseUpdater = void 0;
var $ = require("jquery");
var DatabaseUpdater = /** @class */ (function () {
    function DatabaseUpdater() {
        this.wowheadScraperDomain = 'http://127.0.0.1:5000';
        this.updateSteps = [
            {
                id: 'clearData',
                progressName: 'Clearing data cache',
                successName: 'Data cache cleared',
                errorName: 'Could not clear data cache',
                relativeLink: '/clear-data'
            },
            {
                id: 'professions',
                progressName: 'Fetching professions',
                successName: 'Professions updated',
                errorName: 'Could not update professions',
                relativeLink: '/professions'
            },
            {
                id: 'locations',
                progressName: 'Fetching locations',
                successName: 'Locations updated',
                errorName: 'Could not update locations',
                relativeLink: '/locations'
            },
            {
                id: 'vendors',
                progressName: 'Fetching vendors',
                successName: 'Vendors updated',
                errorName: 'Could not update vendors',
                relativeLink: '/vendors'
            },
            {
                id: 'reagents',
                progressName: 'Fetching reagents',
                successName: 'Reagents updated',
                errorName: 'Could not update reagents',
                relativeLink: '/reagents'
            },
            {
                id: 'reagentDetails',
                progressName: 'Fetching reagent details',
                successName: 'Reagent details updated',
                errorName: 'Could not update reagent details',
                relativeLink: '/reagent-details'
            },
            {
                id: 'craftableItems',
                progressName: 'Fetching craftable items',
                successName: 'Craftable items updated',
                errorName: 'Could not update craftable items',
                relativeLink: '/craftable-items'
            },
            {
                id: 'professionData',
                progressName: 'Fetching profession data',
                successName: 'Profession data updated',
                errorName: 'Could not update profession data',
                relativeLink: '/profession-data'
            },
            {
                id: 'recipeDetails',
                progressName: 'Fetching recipe details',
                successName: 'Recipe details updated',
                errorName: 'Could not update recipe details',
                relativeLink: '/recipe-details'
            },
            {
                id: 'checkData',
                progressName: 'Checking data',
                successName: 'Data is valid',
                errorName: 'Could not check data',
                relativeLink: '/check-data'
            }
        ];
    }
    /**
     * @param {string} [startFrom=null] - id string of the step to start from. Otherwise start from beginning.
     */
    DatabaseUpdater.prototype.update = function (startFrom, clearProgress) {
        var _this = this;
        if (startFrom === void 0) { startFrom = null; }
        if (clearProgress === void 0) { clearProgress = true; }
        // Clear the progress box if clear progress is true
        if (clearProgress) {
            $('#progressBox').children().each(function (index, child) {
                child.parentElement.removeChild(child);
            });
        }
        // Set update button to in progress
        var updateButton = $('#updateButton');
        updateButton.html('In progress' + '<i class="fas fa-spin fa-sync-alt ml-2"></i>');
        updateButton.prop("disabled", true);
        // Because Javascript might convert an id string into an element. Make sure that startFrom is a string
        if (startFrom !== null) {
            // To convert HTMLDivElement to string. Convert to unknown first
            var startFromUnknown = startFrom;
            var startFromElement = startFromUnknown;
            startFrom = startFromElement.id;
        }
        // Iterate through update steps
        var hasEncounteredError = false;
        var hasStartFromBeenReached = false;
        var executeSteps = function () { return __awaiter(_this, void 0, void 0, function () {
            var _loop_1, _i, _a, step;
            var _this = this;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        _loop_1 = function (step) {
                            return __generator(this, function (_a) {
                                switch (_a.label) {
                                    case 0:
                                        if (!(hasEncounteredError === false)) return [3 /*break*/, 2];
                                        // Check if has start has been reached
                                        if (startFrom !== null) {
                                            if (step.id === startFrom) {
                                                hasStartFromBeenReached = true;
                                            }
                                        }
                                        if (!(startFrom === null || hasStartFromBeenReached === true)) return [3 /*break*/, 2];
                                        return [4 /*yield*/, new Promise(function (resolve) { return setTimeout(function () {
                                                // Add message to progress box if it doesn't exist
                                                if ($('#' + step.id).length <= 0) {
                                                    var message = document.createElement('div');
                                                    message.id = step.id;
                                                    message.innerText = step.progressName;
                                                    message.classList.add('alert');
                                                    $('#progressBox').append(message);
                                                }
                                                // Execute step
                                                $.ajax({
                                                    url: _this.wowheadScraperDomain + step.relativeLink,
                                                    type: 'GET',
                                                    cache: false,
                                                }).done(function (response) {
                                                    // Get time string from response
                                                    var years = response.response_time.years === 0 ? '' : response.response_time.years + ' years, ';
                                                    var months = response.response_time.months === 0 ? '' : response.response_time.months + ' months, ';
                                                    var days = response.response_time.days === 0 ? '' : response.response_time.days + ' days, ';
                                                    var hours = response.response_time.hours === 0 ? '' : response.response_time.hours + ' hours, ';
                                                    var minutes = response.response_time.minutes === 0 ? '' : response.response_time.minutes + ' minutes, ';
                                                    var seconds = response.response_time.seconds + ' seconds ';
                                                    // Get message
                                                    var progressMessage = $('#' + step.id);
                                                    // Remove alert-danger class
                                                    progressMessage.removeClass('alert-danger');
                                                    // Edit element
                                                    progressMessage.html(step.successName + '. completed in: ' +
                                                        years + months + days + hours + minutes + seconds +
                                                        '<i class="fas fa-check"></i>');
                                                    progressMessage.addClass('alert-success');
                                                }).fail(function () {
                                                    // Get message
                                                    var progressMessage = $('#' + step.id);
                                                    // Edit element
                                                    progressMessage.html(step.errorName +
                                                        ' <i class="fas fa-times mr-2"></i>' +
                                                        '<button type="button" class="btn btn-primary" ' +
                                                        'onclick="window.app.getDatabaseUpdater().update(' + step.id + false +
                                                        ')">Restart</button>');
                                                    progressMessage.addClass('alert-danger');
                                                    hasEncounteredError = true;
                                                }).always(function () {
                                                    resolve(resolve);
                                                });
                                            }, 0); })];
                                    case 1:
                                        _a.sent();
                                        _a.label = 2;
                                    case 2: return [2 /*return*/];
                                }
                            });
                        };
                        _i = 0, _a = this.updateSteps;
                        _b.label = 1;
                    case 1:
                        if (!(_i < _a.length)) return [3 /*break*/, 4];
                        step = _a[_i];
                        return [5 /*yield**/, _loop_1(step)];
                    case 2:
                        _b.sent();
                        _b.label = 3;
                    case 3:
                        _i++;
                        return [3 /*break*/, 1];
                    case 4: return [2 /*return*/];
                }
            });
        }); };
        executeSteps().then(function () {
            // Re-enable update button
            updateButton.html('Restart update');
            updateButton.prop("disabled", false);
        });
    };
    return DatabaseUpdater;
}());
exports.DatabaseUpdater = DatabaseUpdater;
