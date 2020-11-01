import * as $ from 'jquery';

export class DatabaseUpdater {

    private wowheadScraperDomain = 'http://127.0.0.1:5000';
    private updateSteps: {
        id: string,
        progressName: string,
        successName: string,
        errorName: string
        relativeLink: string
    }[] = [
        {
            id:             'clearData',
            progressName:   'Clearing data cache',
            successName:    'Data cache cleared',
            errorName:      'Could not clear data cache',
            relativeLink:   '/clear-data'
        },
        {
            id:             'professions',
            progressName:   'Fetching professions',
            successName:    'Professions updated',
            errorName:      'Could not update professions',
            relativeLink:   '/professions'
        },
        {
            id:             'locations',
            progressName:   'Fetching locations',
            successName:    'Locations updated',
            errorName:      'Could not update locations',
            relativeLink:   '/locations'
        },
        {
            id:             'vendors',
            progressName:   'Fetching vendors',
            successName:    'Vendors updated',
            errorName:      'Could not update vendors',
            relativeLink:   '/vendors'
        },
        {
            id:             'reagents',
            progressName:   'Fetching reagents',
            successName:     'Reagents updated',
            errorName:      'Could not update reagents',
            relativeLink:   '/reagents'
        },
        {
            id:             'reagentDetails',
            progressName:   'Fetching reagent details',
            successName:    'Reagent details updated',
            errorName:      'Could not update reagent details',
            relativeLink:   '/reagent-details'
        },
        {
            id:             'craftableItems',
            progressName:   'Fetching craftable items',
            successName:    'Craftable items updated',
            errorName:      'Could not update craftable items',
            relativeLink:   '/craftable-items'
        },
        {
            id:             'professionData',
            progressName:   'Fetching profession data',
            successName:    'Profession data updated',
            errorName:      'Could not update profession data',
            relativeLink:   '/profession-data'
        },
        {
            id:             'recipeDetails',
            progressName:   'Fetching recipe details',
            successName:    'Recipe details updated',
            errorName:      'Could not update recipe details',
            relativeLink:   '/recipe-details'
        },
        {
            id:             'checkData',
            progressName:   'Checking data',
            successName:    'Data is valid',
            errorName:      'Could not check data',
            relativeLink:   '/check-data'
        }
    ];

    public constructor() {

    }

    /**
     * @param {string} [startFrom=null] - id string of the step to start from. Otherwise start from beginning.
     */
    public update(startFrom: string = null, clearProgress = true) : void {

        // Clear the progress box if clear progress is true
        if (clearProgress) {
            $('#progressBox').children().each((index: number, child: HTMLElement) => {
                child.parentElement.removeChild(child);
            });
        }

        // Set update button to in progress
        let updateButton = $('#updateButton');
        updateButton.html('In progress' + '<i class="fas fa-spin fa-sync-alt ml-2"></i>');
        updateButton.prop("disabled", true);

        // Because Javascript might convert an id string into an element. Make sure that startFrom is a string
        if (startFrom !== null) {
            // To convert HTMLDivElement to string. Convert to unknown first
            let startFromUnknown: unknown = <unknown>startFrom;
            let startFromElement: HTMLDivElement = <HTMLDivElement>startFromUnknown;
            startFrom = startFromElement.id;
        }

        // Iterate through update steps
        let hasEncounteredError = false;
        let hasStartFromBeenReached = false;
        let executeSteps = async () => {
            for (let step of this.updateSteps) {

                // Stop if an error has been encountered
                if (hasEncounteredError === false) {

                    // Check if has start has been reached
                    if (startFrom !== null) {
                        if (step.id === startFrom) {
                            hasStartFromBeenReached = true;
                        }
                    }

                    // Execute step if start from is unspecified or start from has been reached
                    if (startFrom === null || hasStartFromBeenReached === true) {

                        await new Promise(resolve => setTimeout(() => {
                            // Add message to progress box if it doesn't exist
                            if ($('#' + step.id).length <= 0) {
                                let message = document.createElement('div');
                                message.id = step.id;
                                message.innerText = step.progressName;
                                message.classList.add('alert');
                                $('#progressBox').append(message);
                            }

                            // Execute step
                            $.ajax({
                                url: this.wowheadScraperDomain + step.relativeLink,
                                type: 'GET',
                                cache: false,
                            }).done((response: {
                                response_time: {
                                    years: number,
                                    months: number,
                                    days: number,
                                    hours: number,
                                    minutes: number,
                                    seconds: number
                                },
                                scraper_time: {
                                    years: number,
                                    months: number,
                                    days: number,
                                    hours: number,
                                    minutes: number,
                                    seconds: number
                                }
                            }) => {
                                // Get time string from response
                                let years = response.response_time.years === 0      ? '' : response.response_time.years + ' years, '
                                let months = response.response_time.months === 0    ? '' : response.response_time.months + ' months, '
                                let days = response.response_time.days === 0        ? '' : response.response_time.days + ' days, '
                                let hours = response.response_time.hours === 0      ? '' : response.response_time.hours + ' hours, '
                                let minutes = response.response_time.minutes === 0  ? '' : response.response_time.minutes + ' minutes, '
                                let seconds = response.response_time.seconds + ' seconds '

                                // Get message
                                let progressMessage = $('#' + step.id);

                                // Remove alert-danger class
                                progressMessage.removeClass('alert-danger');

                                // Edit element
                                progressMessage.html(   step.successName + '. completed in: ' +
                                    years + months + days + hours + minutes + seconds +
                                    '<i class="fas fa-check"></i>');
                                progressMessage.addClass('alert-success');
                            }).fail(() => {
                                // Get message
                                let progressMessage = $('#' + step.id);

                                // Edit element
                                progressMessage.html(   step.errorName +
                                    ' <i class="fas fa-times mr-2"></i>' +
                                    '<button type="button" class="btn btn-primary" ' +
                                    'onclick="window.app.getDatabaseUpdater().update(' + step.id + false +
                                    ')">Restart</button>');
                                progressMessage.addClass('alert-danger');
                                hasEncounteredError = true;
                            }).always(() => {
                                resolve(resolve);
                            });
                        }, 0));
                    }
                }
            }
        }
        executeSteps().then(() => {

            // Re-enable update button
            updateButton.html('Restart update');
            updateButton.prop("disabled", false);
        });
    }
}