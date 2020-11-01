"use strict";
exports.__esModule = true;
exports.DatabaseEntity = void 0;
var DatabaseEntity = /** @class */ (function () {
    function DatabaseEntity(id, htmlString) {
        this.id = id;
        this.htmlString = htmlString;
    }
    // Getters and setters
    DatabaseEntity.prototype.getId = function () {
        return this.id;
    };
    DatabaseEntity.prototype.setId = function (id) {
        this.id = id;
    };
    DatabaseEntity.prototype.getHtmlString = function () {
        return this.htmlString;
    };
    DatabaseEntity.prototype.setHtmlString = function (htmlString) {
        this.htmlString = htmlString;
    };
    return DatabaseEntity;
}());
exports.DatabaseEntity = DatabaseEntity;
