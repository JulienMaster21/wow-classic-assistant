import {Input} from "./Input";
import {TextInput} from "./TextInput";
import * as $ from 'jquery';
import {Form} from "./Form";
import {EmailInput} from "./EmailInput";
import {PasswordInput} from "./PasswordInput";
import {ConfirmPasswordInput} from "./ConfirmPasswordInput";

export class ClientValidator {

    private allPossibleInputs: Input[];
    private forms: Form[];

    public constructor() {

        // Initialise arrays
        this.allPossibleInputs = [];
        this.forms = [];

        // Get all possible inputs
        this.createPossibleInputs()

        // Create array of all input identifiers and names
        let allIdentifiers = [];
        this.allPossibleInputs.forEach((input) => {
            allIdentifiers.push(input.getIdentifier());
        });

        // Get all forms on the page
        let formElements = $('form').get();
        formElements.forEach((element: HTMLFormElement) => {
            this.forms.push(new Form(element, allIdentifiers, this.allPossibleInputs));
        });
    }

    public createPossibleInputs() : void {

        // Name
        this.getAllPossibleInputs().push(new TextInput( 'username',
                                                        'Username',
                                                        1,
                                                        255,
                                                        new RegExp('[a-zA-Z0-9]')));

        // Email
        this.getAllPossibleInputs().push(new EmailInput( 'email',
                                                        'Email',
                                                        1,
                                                        255,
                                                        new RegExp('[a-zA-Z0-9]')));

        // Password
        this.getAllPossibleInputs().push(new PasswordInput( 'password',
                                                        'Password',
                                                        10,
                                                        255,
                                                        new RegExp('[a-zA-Z0-9]')));

        // Register Password
        this.getAllPossibleInputs().push(new ConfirmPasswordInput(  'passwordfirst',
                                                                    'passwordsecond',
                                                                    'Password',
                                                                    'Confirm Password',
                                                                    10,
                                                                    255,
                                                                    new RegExp('[a-zA-Z0-9]')));
    }

    // Getters and setters
    public getAllPossibleInputs() : Input[] {
        return this.allPossibleInputs;
    }

    public setAllPossibleInputs(possibleInputs: Input[]) : void {
        this.allPossibleInputs = possibleInputs;
    }

    public getForms() : Form[] {
        return this.forms;
    }

    public setForms(forms: Form[]) : void {
        this.forms = forms;
    }
}