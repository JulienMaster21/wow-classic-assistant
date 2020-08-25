import {Input} from "./Input";
import {TextInput} from "./TextInput";
import {EmailInput} from "./EmailInput";
import {ConfirmPasswordInput} from "./ConfirmPasswordInput";

export class Form {

    private element: HTMLFormElement;
    private inputs: Input[];
    private submitButton: HTMLButtonElement;
    private validationPassed: boolean;

    public constructor(element: HTMLFormElement,
                       allIdentifiers: string[],
                       allPossibleInputs: Input[]) {

        // Initialise inputs array
        this.inputs = [];

        // Initialise validation passed as false
        this.validationPassed = false;

        // Initialise confirm password needs second input as false
        let confirmPasswordNeedsSecondInput = false;

        // Get inputs and submit button
        this.element = element;
        let formName = this.element.name;
        this.element.childNodes.forEach((formGroup: ChildNode) => {
            // Check if element is a div and has children
            if (formGroup.nodeName === 'DIV' &&
                formGroup.childNodes.length > 0) {
                console.log(formGroup);
                formGroup.childNodes.forEach((child: ChildNode) => {
                    if (child.nodeName === 'INPUT') {
                        let input: HTMLInputElement = <HTMLInputElement>child;
                        let inputIdentifier = ( input.name
                                                .replace(new RegExp('[\\[\\]]', 'g'), ''))
                                                .slice(formName.length);
                        let index: number = allIdentifiers.indexOf(inputIdentifier);
                        if (confirmPasswordNeedsSecondInput) {
                            let confirmPasswordInput = <ConfirmPasswordInput>this.inputs[this.inputs.length - 1]
                            confirmPasswordInput.setSecondElement(input);
                            confirmPasswordNeedsSecondInput = false;
                        }
                        if (index !== -1) {
                            if (input.type === 'text') {
                                let textInput = <TextInput>allPossibleInputs[index];
                                this.inputs.push(new TextInput( textInput.getIdentifier(),
                                                                textInput.getName(),
                                                                textInput.getMinimumSize(),
                                                                textInput.getMaximumSize(),
                                                                textInput.getAllowedCharacters(),
                                                                input));
                            }
                            if (input.type === 'password') {
                                if (allPossibleInputs[index] instanceof ConfirmPasswordInput) {
                                    let confirmPasswordInput = <ConfirmPasswordInput>allPossibleInputs[index];
                                    this.inputs.push(new ConfirmPasswordInput(  confirmPasswordInput.getIdentifier(),
                                                                                confirmPasswordInput.getSecondIdentifier(),
                                                                                confirmPasswordInput.getName(),
                                                                                confirmPasswordInput.getSecondName(),
                                                                                confirmPasswordInput.getMinimumSize(),
                                                                                confirmPasswordInput.getMaximumSize(),
                                                                                confirmPasswordInput.getAllowedCharacters(),
                                                                                input));
                                    confirmPasswordNeedsSecondInput = true;
                                }
                                else {
                                    let passwordInput = <TextInput>allPossibleInputs[index];
                                    this.inputs.push(new TextInput( passwordInput.getIdentifier(),
                                                                    passwordInput.getName(),
                                                                    passwordInput.getMinimumSize(),
                                                                    passwordInput.getMaximumSize(),
                                                                    passwordInput.getAllowedCharacters(),
                                                                    input));
                                }
                            }
                            if (input.type === 'email') {
                                let emailInput = <EmailInput>allPossibleInputs[index];
                                this.inputs.push(new EmailInput(emailInput.getIdentifier(),
                                                                emailInput.getName(),
                                                                emailInput.getMinimumSize(),
                                                                emailInput.getMaximumSize(),
                                                                emailInput.getAllowedCharacters(),
                                                                input));
                            }
                        }
                    }
                    else if (child.nodeName === 'BUTTON') {
                        this.submitButton = <HTMLButtonElement>child;
                    }
                });
            }
        });

        // Set on submit event handler on form element
        this.element.addEventListener('submit', (event: Event) => {

            // Check all inputs
            this.inputs.forEach((input: Input) => {
                input.checkValidation(input.getElement());
            });
            this.checkValidation();

            // If validation didn't pass then prevent submission
            if (!this.validationPassed) {
                event.preventDefault();
            }
        });

        // Set on blur event handler on inputs
        this.inputs.forEach((input) => {
            input.getElement().addEventListener('blur', () => {
                input.checkValidation(input.getElement());
                this.checkValidation();
            });
        });

        // If form has no inputs to check then pass validation
        if (this.inputs.length === 0) {
            this.validationPassed = true;
        }
    }

    public checkValidation() : void {

        let validationPassed: boolean = true;

        // check if inputs pass validation
        this.inputs.forEach((input: Input) => {
            if (!input.getValidationPassed()) {
                validationPassed = false;
            }
        });

        this.validationPassed = validationPassed;
    }

    // Getters and setters
    public getElement() : HTMLFormElement {
        return this.element;
    }

    public setElement(element: HTMLFormElement) : void {
        this.element = element;
    }

    public getInputs(): Input[] {
        return this.inputs;
    }

    public setInputs(inputs: Input[]) : void {
        this.inputs = inputs;
    }

    public getSubmitButton(): HTMLButtonElement {
        return this.submitButton;
    }

    public setSubmitButton(button: HTMLButtonElement) : void {
        this.submitButton = button;
    }

    public getValidationPassed() : boolean {
        return this.validationPassed;
    }

    public setValidationPassed(validationPassed: boolean) : void {
        this.validationPassed = validationPassed;
    }
}