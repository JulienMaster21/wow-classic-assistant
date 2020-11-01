import {app} from './App';
import * as $ from 'jquery';
import {DatabaseEntityCollection} from "./DatabaseEntityCollection";
import {DatabaseEntity} from "./DatabaseEntity";

export class NavigationHandler {

    private entityCollections: DatabaseEntityCollection[];
    private currentEntityCollection: DatabaseEntityCollection;
    private selectedLength: number;

    private tableElement: HTMLTableElement;
    private columnElements: HTMLTableHeaderCellElement[];
    private rowElements: HTMLTableRowElement[];
    private selectElement: HTMLSelectElement;
    private selectOptions: HTMLOptionElement[];
    private navigationElements: HTMLSpanElement[];
    private pageSelect: HTMLSelectElement;

    public constructor(tableElement: HTMLTableElement) {

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
        this.selectElement = <HTMLSelectElement>$('#entityRange').get()[0];
        this.selectElement.addEventListener('change', () => {
            // Set selected length
            this.selectedLength = parseInt(this.selectElement.selectedOptions[0].value);
            // Get all entities and reorder them
            let allEntities: {'id': number, 'htmlString': string}[] = [];
            this.entityCollections.forEach((entityCollection: DatabaseEntityCollection) => {
                entityCollection.getEntities().forEach((entity: DatabaseEntity) => {
                    allEntities.push({'id': entity.getId(), 'htmlString': entity.getHtmlString()});
                });
            });
            this.organiseDatabaseEntities(allEntities);
            this.updatePageSelectOptions();
            // Set page select and current entity collection to default
            this.pageSelect.value = '0';
            this.currentEntityCollection = this.entityCollections[0];
            this.updateTable();
        });
        this.pageSelect = <HTMLSelectElement>$('#pageSelect').get()[0];
        this.pageSelect.addEventListener('change', () => {
            let collectionId = this.pageSelect.selectedOptions[0].value;
            this.currentEntityCollection = this.entityCollections[collectionId];
            this.updateTable();
        });

        let rows = <HTMLTableRowElement[]>[].slice.call(this.tableElement.children);
        rows.forEach((row: HTMLTableRowElement) => {
            this.rowElements.push(row);
        });

        let options: number[] = [
            5,
            10
        ];
        options.forEach((option: number) => {
            let optionId: string = '#entityFilter' + option.toString();
            this.selectOptions.push(<HTMLOptionElement>$(optionId).get()[0]);
        });

        let navigation: string[] = [
            'First',
            'Previous',
            'Next',
            'Last'
        ];
        navigation.forEach((element: string) => {
            let navigationId: string = '#controlsTo' + element.toString();
            this.navigationElements.push(<HTMLSpanElement>$(navigationId).get()[0]);
        });

        // Get entities
        let currentUrl : string = window.location.href;
        let classSection : string = /[^/]+(?=\/$|$)/.exec(currentUrl).toString();
        $.ajax('/api/' + classSection + '/row').done((entities) => {

            // Organise entities into collections
            this.organiseDatabaseEntities(entities);

            // Set current entity collection to the first
            this.currentEntityCollection = this.entityCollections[0];

        }).then(() => {

            // Add first rows to table
            this.updateTable();

            // Change loading message on caption
            let classWords = (classSection.split('-'));
            for (let wordId = 0; wordId < classWords.length; wordId++) {
                classWords[wordId] = classWords[wordId].charAt(0).toUpperCase() + classWords[wordId].substring(1);
            }
            let className = classWords.join(' ');
            this.tableElement.caption.innerText = 'List of ' + className;

            // Set on click handler on navigation elements
            this.navigationElements[0].addEventListener('click', () => {
                this.currentEntityCollection = this.entityCollections[0];
                this.pageSelect.value = '0';
                this.updateTable();
            });
            this.navigationElements[1].addEventListener('click', () => {
                let currentId = this.entityCollections.indexOf(this.currentEntityCollection);
                if (currentId > 0) {
                    this.currentEntityCollection = this.entityCollections[currentId - 1];
                    this.pageSelect.value = (currentId - 1).toString();
                    this.updateTable();
                }
            });
            this.navigationElements[2].addEventListener('click', () => {
                let currentId = this.entityCollections.indexOf(this.currentEntityCollection);
                if (currentId < this.entityCollections.length - 1) {
                    let currentId = this.entityCollections.indexOf(this.currentEntityCollection);
                    this.currentEntityCollection = this.entityCollections[currentId + 1];
                    this.pageSelect.value = (currentId + 1).toString();
                    this.updateTable();
                }
            });
            this.navigationElements[3].addEventListener('click', () => {
                this.currentEntityCollection = this.entityCollections[this.entityCollections.length - 1];
                this.pageSelect.value = (this.entityCollections.length - 1).toString();
                this.updateTable();
            });

            this.updatePageSelectOptions();
        });
    }

    public updateTable() {

        this.removeRows();
        this.addRows();
        this.checkControls();
    }

    public removeRows() : void {

        // Remove all children from table
        let children : HTMLTableRowElement[] = [].slice.call(this.tableElement.tBodies['0'].children);
        children.forEach((child: HTMLTableRowElement) => {
            child.remove();
        });

        // Clear row elements array
        this.rowElements = [];
    }

    public addRows() : void {

        let rowsString = '';
        this.currentEntityCollection.getEntities().forEach((entity: DatabaseEntity) => {
            rowsString += entity.getHtmlString();
        });
        this.tableElement.tBodies['0'].innerHTML = rowsString;
    }

    public checkControls() : void {

        // Get current entity collection id
        let currentId = this.entityCollections.indexOf(this.currentEntityCollection);

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
    }

    public organiseDatabaseEntities(entities: {id: number, htmlString: string}[]) : void {

        // Clear current entity collections
        this.entityCollections = [];

        // Add entities to the right collection
        let entityArray : DatabaseEntity[] = [];
        entities.forEach((entity: {id: number, htmlString: string}) => {
            entityArray.push(new DatabaseEntity(entity.id, entity.htmlString));

            if (entityArray.length >= this.selectedLength || entities.indexOf(entity) === entities.length - 1) {
                this.entityCollections.push(new DatabaseEntityCollection(entityArray));
                entityArray = [];
            }
        });
    }

    public updatePageSelectOptions() : void {

        // Clear all page select options
        [].slice.call(this.pageSelect.options).forEach((option) => {
            option.remove();
        });

        // Update page select options
        this.entityCollections.forEach((entityCollection: DatabaseEntityCollection) => {
            let pageOption = document.createElement('option');
            pageOption.value = this.entityCollections.indexOf(entityCollection).toString();
            pageOption.innerText = entityCollection.getFirstId() + ' - ' + entityCollection.getLastId();
            this.pageSelect.appendChild(pageOption);
        });
    }

    // Getters and setters
    public getEntityCollections() : DatabaseEntityCollection[] {
        return this.entityCollections;
    }

    public setEntityCollections(entityCollections: DatabaseEntityCollection[]) : void {
        this.entityCollections = entityCollections;
    }

    public getCurrentEntityCollection() : DatabaseEntityCollection {
        return this.currentEntityCollection;
    }

    public setCurrentEntityCollection(currentEntityCollection: DatabaseEntityCollection) : void {
        this.currentEntityCollection = currentEntityCollection;
    }

    public getSelectedLength() : number {
        return this.selectedLength;
    }

    public setSelectedLength(selectedLength: number) : void {
        this.selectedLength = selectedLength;
    }

    public getTableElement() : HTMLTableElement {
        return this.tableElement;
    }

    public setTableElement(tableElement: HTMLTableElement) : void {
        this.tableElement = tableElement;
    }

    public getColumnElements() : HTMLTableHeaderCellElement[] {
        return this.columnElements;
    }

    public setColumnElements(columnElements: HTMLTableHeaderCellElement[]) : void {
        this.columnElements = columnElements;
    }

    public getRowElements() : HTMLTableRowElement[] {
        return this.rowElements;
    }

    public setRowElements(rowElements: HTMLTableRowElement[]) : void {
        this.rowElements = rowElements;
    }

    public getSelectElement() : HTMLSelectElement {
        return this.selectElement;
    }

    public setSelectElement(selectElement: HTMLSelectElement) : void {
        this.selectElement = selectElement;
    }

    public getSelectOptions() : HTMLOptionElement[] {
        return this.selectOptions;
    }

    public setSelectOptions(selectOptions: HTMLOptionElement[]) : void {
        this.selectOptions = selectOptions;
    }

    public getNavigationElements() : HTMLSpanElement[] {
        return this.navigationElements;
    }

    public setNavigationElements(navigationElements: HTMLSpanElement[]) : void {
        this.navigationElements = navigationElements;
    }

    public getPageSelect() : HTMLSelectElement {
        return this.pageSelect;
    }

    public setPageSelect(pageSelect: HTMLSelectElement) : void {
        this.pageSelect = pageSelect;
    }
}

// Instantiate and attach navigation handler to app
let navigationHandler = new NavigationHandler(<HTMLTableElement>$('table').get()[0]);
app.setNavigationHandler(navigationHandler);