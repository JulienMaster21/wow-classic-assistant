"use strict";
exports.__esModule = true;
var DatabaseEntityCollection = /** @class */ (function () {
    function DatabaseEntityCollection(entities) {
        this.databaseEntities = entities;
        this.firstEntity = this.databaseEntities[0];
        this.lastEntity = this.databaseEntities[this.databaseEntities.length - 1];
    }
    // Getters and setters
    DatabaseEntityCollection.prototype.getEntities = function () {
        return this.databaseEntities;
    };
    DatabaseEntityCollection.prototype.getFirstEntity = function () {
        return this.firstEntity;
    };
    DatabaseEntityCollection.prototype.getFirstId = function () {
        return this.firstEntity.getId();
    };
    DatabaseEntityCollection.prototype.getLastEntity = function () {
        return this.lastEntity;
    };
    DatabaseEntityCollection.prototype.getLastId = function () {
        return this.lastEntity.getId();
    };
    return DatabaseEntityCollection;
}());
exports.DatabaseEntityCollection = DatabaseEntityCollection;
