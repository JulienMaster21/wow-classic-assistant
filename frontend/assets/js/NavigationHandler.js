"use strict";
exports.__esModule = true;
var App_1 = require("./App");
var $ = require("jquery");
var DatabaseEntityCollection_1 = require("./DatabaseEntityCollection");
var DatabaseEntity_1 = require("./DatabaseEntity");
var NavigationHandler = /** @class */ (function () {
    function NavigationHandler(tableElement) {
        var _this = this;
        // Set default for selected length
        this.selectedLength = 10;
        // Instantiate arrays
        this.entityCollections = [];
        this.columnElements = [];
        this.rowElements = [];
        this.selectOptions = [];
        this.navigationElements = [];
        // Get html elements
        this.tableElement = tableElement;
        this.selectElement = $('#entityRange').get()[0];
        this.selectElement.addEventListener('change', function () {
            // Set selected length
            _this.selectedLength = parseInt(_this.selectElement.selectedOptions[0].value);
            // Get all entities and reorder them
            var allEntities = [];
            _this.entityCollections.forEach(function (entityCollection) {
                entityCollection.getEntities().forEach(function (entity) {
                    allEntities.push({ 'id': entity.getId(), 'htmlString': entity.getHtmlString() });
                });
            });
            _this.organiseDatabaseEntities(allEntities);
            _this.updatePageSelectOptions();
            // Set page select and current entity collection to default
            _this.pageSelect.value = '0';
            _this.currentEntityCollection = _this.entityCollections[0];
            _this.updateTable();
        });
        this.pageSelect = $('#pageSelect').get()[0];
        this.pageSelect.addEventListener('change', function () {
            var collectionId = _this.pageSelect.selectedOptions[0].value;
            _this.currentEntityCollection = _this.entityCollections[collectionId];
            _this.updateTable();
        });
        var rows = [].slice.call(this.tableElement.children);
        rows.forEach(function (row) {
            _this.rowElements.push(row);
        });
        var options = [
            5,
            10
        ];
        options.forEach(function (option) {
            var optionId = '#entityFilter' + option.toString();
            _this.selectOptions.push($(optionId).get()[0]);
        });
        var navigation = [
            'First',
            'Previous',
            'Next',
            'Last'
        ];
        navigation.forEach(function (element) {
            var navigationId = '#controlsTo' + element.toString();
            _this.navigationElements.push($(navigationId).get()[0]);
        });
        // Get entities
        var currentUrl = window.location.href;
        var classSection = /[^/]+(?=\/$|$)/.exec(currentUrl).toString();
        $.ajax('/api/' + classSection + '/row').done(function (entities) {
            // Organise entities into collections
            _this.organiseDatabaseEntities(entities);
            // Set current entity collection to the first
            _this.currentEntityCollection = _this.entityCollections[0];
        }).then(function () {
            // Add first rows to table
            _this.updateTable();
            // Change loading message on caption
            var classWords = (classSection.split('-'));
            for (var wordId = 0; wordId < classWords.length; wordId++) {
                classWords[wordId] = classWords[wordId].charAt(0).toUpperCase() + classWords[wordId].substring(1);
            }
            var className = classWords.join(' ');
            _this.tableElement.caption.innerText = 'List of ' + className;
            // Set on click handler on navigation elements
            _this.navigationElements[0].addEventListener('click', function () {
                _this.currentEntityCollection = _this.entityCollections[0];
                _this.pageSelect.value = '0';
                _this.updateTable();
            });
            _this.navigationElements[1].addEventListener('click', function () {
                var currentId = _this.entityCollections.indexOf(_this.currentEntityCollection);
                if (currentId > 0) {
                    _this.currentEntityCollection = _this.entityCollections[currentId - 1];
                    _this.pageSelect.value = (currentId - 1).toString();
                    _this.updateTable();
                }
            });
            _this.navigationElements[2].addEventListener('click', function () {
                var currentId = _this.entityCollections.indexOf(_this.currentEntityCollection);
                if (currentId < _this.entityCollections.length - 1) {
                    var currentId_1 = _this.entityCollections.indexOf(_this.currentEntityCollection);
                    _this.currentEntityCollection = _this.entityCollections[currentId_1 + 1];
                    _this.pageSelect.value = (currentId_1 + 1).toString();
                    _this.updateTable();
                }
            });
            _this.navigationElements[3].addEventListener('click', function () {
                _this.currentEntityCollection = _this.entityCollections[_this.entityCollections.length - 1];
                _this.pageSelect.value = (_this.entityCollections.length - 1).toString();
                _this.updateTable();
            });
            _this.updatePageSelectOptions();
            console.log('loading is done');
        });
    }
    NavigationHandler.prototype.updateTable = function () {
        this.removeRows();
        this.addRows();
        this.checkControls();
    };
    NavigationHandler.prototype.removeRows = function () {
        // Remove all children from table
        var children = [].slice.call(this.tableElement.tBodies['0'].children);
        children.forEach(function (child) {
            child.remove();
        });
        // Clear row elements array
        this.rowElements = [];
    };
    NavigationHandler.prototype.addRows = function () {
        var rowsString = '';
        this.currentEntityCollection.getEntities().forEach(function (entity) {
            rowsString += entity.getHtmlString();
        });
        this.tableElement.tBodies['0'].innerHTML = rowsString;
    };
    NavigationHandler.prototype.checkControls = function () {
        // Get current entity collection id
        var currentId = this.entityCollections.indexOf(this.currentEntityCollection);
        // On first and second page
        if (currentId < 1) {
            this.navigationElements[0].classList.add('invisible');
            this.navigationElements[1].classList.add('invisible');
        }
        else if (currentId < 2) {
            this.navigationElements[0].classList.add('invisible');
            this.navigationElements[1].classList.remove('invisible');
        }
        else {
            this.navigationElements[0].classList.remove('invisible');
            this.navigationElements[1].classList.remove('invisible');
        }
        // last and second to last page
        if (currentId >= this.entityCollections.length - 1) {
            this.navigationElements[2].classList.add('invisible');
            this.navigationElements[3].classList.add('invisible');
        }
        else if (currentId >= this.entityCollections.length - 2) {
            this.navigationElements[2].classList.remove('invisible');
            this.navigationElements[3].classList.add('invisible');
        }
        else {
            this.navigationElements[2].classList.remove('invisible');
            this.navigationElements[3].classList.remove('invisible');
        }
    };
    NavigationHandler.prototype.organiseDatabaseEntities = function (entities) {
        var _this = this;
        // Clear current entity collections
        this.entityCollections = [];
        // Add entities to the right collection
        var entityArray = [];
        entities.forEach(function (entity) {
            entityArray.push(new DatabaseEntity_1.DatabaseEntity(entity.id, entity.htmlString));
            if (entityArray.length >= _this.selectedLength || entities.indexOf(entity) === entities.length - 1) {
                _this.entityCollections.push(new DatabaseEntityCollection_1.DatabaseEntityCollection(entityArray));
                entityArray = [];
            }
        });
    };
    NavigationHandler.prototype.updatePageSelectOptions = function () {
        var _this = this;
        // Clear all page select options
        [].slice.call(this.pageSelect.options).forEach(function (option) {
            option.remove();
        });
        // Update page select options
        this.entityCollections.forEach(function (entityCollection) {
            var pageOption = document.createElement('option');
            pageOption.value = _this.entityCollections.indexOf(entityCollection).toString();
            pageOption.innerText = entityCollection.getFirstId() + ' - ' + entityCollection.getLastId();
            _this.pageSelect.appendChild(pageOption);
        });
    };
    // Getters and setters
    NavigationHandler.prototype.getEntityCollections = function () {
        return this.entityCollections;
    };
    NavigationHandler.prototype.setEntityCollections = function (entityCollections) {
        this.entityCollections = entityCollections;
    };
    NavigationHandler.prototype.getCurrentEntityCollection = function () {
        return this.currentEntityCollection;
    };
    NavigationHandler.prototype.setCurrentEntityCollection = function (currentEntityCollection) {
        this.currentEntityCollection = currentEntityCollection;
    };
    NavigationHandler.prototype.getSelectedLength = function () {
        return this.selectedLength;
    };
    NavigationHandler.prototype.setSelectedLength = function (selectedLength) {
        this.selectedLength = selectedLength;
    };
    NavigationHandler.prototype.getTableElement = function () {
        return this.tableElement;
    };
    NavigationHandler.prototype.setTableElement = function (tableElement) {
        this.tableElement = tableElement;
    };
    NavigationHandler.prototype.getColumnElements = function () {
        return this.columnElements;
    };
    NavigationHandler.prototype.setColumnElements = function (columnElements) {
        this.columnElements = columnElements;
    };
    NavigationHandler.prototype.getRowElements = function () {
        return this.rowElements;
    };
    NavigationHandler.prototype.setRowElements = function (rowElements) {
        this.rowElements = rowElements;
    };
    NavigationHandler.prototype.getSelectElement = function () {
        return this.selectElement;
    };
    NavigationHandler.prototype.setSelectElement = function (selectElement) {
        this.selectElement = selectElement;
    };
    NavigationHandler.prototype.getSelectOptions = function () {
        return this.selectOptions;
    };
    NavigationHandler.prototype.setSelectOptions = function (selectOptions) {
        this.selectOptions = selectOptions;
    };
    NavigationHandler.prototype.getNavigationElements = function () {
        return this.navigationElements;
    };
    NavigationHandler.prototype.setNavigationElements = function (navigationElements) {
        this.navigationElements = navigationElements;
    };
    NavigationHandler.prototype.getPageSelect = function () {
        return this.pageSelect;
    };
    NavigationHandler.prototype.setPageSelect = function (pageSelect) {
        this.pageSelect = pageSelect;
    };
    return NavigationHandler;
}());
exports.NavigationHandler = NavigationHandler;
// Instantiate and attach navigation handler to app
var navigationHandler = new NavigationHandler($('table').get()[0]);
App_1.app.setNavigationHandler(navigationHandler);
