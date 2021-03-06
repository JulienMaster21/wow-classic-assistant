import {ClientValidator} from "./ClientValidator";
import * as $ from 'jquery';
import {NavigationHandler} from "./NavigationHandler";
import {DatabaseUpdater} from "./DatabaseUpdater";
require('bootstrap');
require('@fortawesome/fontawesome-free/css/all.min.css');
require('@fortawesome/fontawesome-free/js/all.js');

export class App {

    private readonly clientValidator: ClientValidator;
    private readonly databaseUpdater: DatabaseUpdater;
    private navigationHandler: NavigationHandler;

    public constructor() {

        // Enable popover
        $(function() {
            // @ts-ignore
            $('[data-toggle="popover"]').popover();
        });

        // Initialise client validation
        this.clientValidator = new ClientValidator();

        // Initialise database updater
        this.databaseUpdater = new DatabaseUpdater();

        // Initialise navigation handler as null
        this.navigationHandler = null;
    }

    public removeFlashMessage(closeButton: HTMLButtonElement) {
        closeButton.parentElement.remove();
    }

    // Getters and setters
    public getClientValidator() : ClientValidator {
        return this.clientValidator;
    }

    public getDatabaseUpdater() {
        return this.databaseUpdater;
    }

    public getNavigationHandler() : NavigationHandler {
        return this.navigationHandler;
    }

    public setNavigationHandler(navigationHandler: NavigationHandler) : void {
        this.navigationHandler = navigationHandler;
    }
}

export const app = new App();
// @ts-ignore
window.app = app;