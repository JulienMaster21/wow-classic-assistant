import {Input} from "./Input";

export class TextInput extends Input {

    private readonly minimumSize: number;
    private readonly maximumSize: number;
    private readonly allowedCharacters: RegExp;

    public constructor(identifier: string,
                       name: string,
                       minimumSize: number,
                       maximumSize: number,
                       allowedCharacters: RegExp,
                       element: HTMLInputElement = null) {

        super(  identifier,
                name,
                element);

        this.minimumSize = minimumSize;
        this.maximumSize = maximumSize;
        this.allowedCharacters = allowedCharacters;
    }

    public checkValidation(element: HTMLInputElement) : void {

        // Initialise local variables
        let validationPassed: boolean = true;
        let messages = [];

        // Perform all checks
        // Check size
        let currentLength = element.value.length
        let sizeMessage = this.checkSize(currentLength);
        if (sizeMessage !== null) {
            messages.push(sizeMessage);
            validationPassed = false;
        }
        // Check allowed characters
        let allowedCharacterMessage = this.checkAllowedCharacters(element.value);
        if (allowedCharacterMessage !== null) {
            messages.push(allowedCharacterMessage);
            validationPassed = false;
        }

        // Check if validation passed
        if (validationPassed) {
            this.setValidationPassed(true);
        }
        else {
            this.setValidationPassed(false);
        }

        // Remove messages
        this.getElement().parentNode.childNodes.forEach((child) => {
            if (child.nodeName === 'P') {
                let paragraph : HTMLParagraphElement = <HTMLParagraphElement>child;
                if (paragraph.classList.contains('error')) {
                    paragraph.remove();
                }
            }
        })

        // Apply styling and messages
        messages.forEach((message) => {
            let messageElement = document.createElement('p');
            messageElement.textContent = message;
            messageElement.classList.add('error');
            this.getElement().parentNode.insertBefore(messageElement, this.getElement());
        })
        // On success
        if (this.getValidationPassed()) {
            this.getElement().classList.remove('error');
            this.getElement().classList.add('success');
        }
        // On error
        else {
            this.getElement().classList.remove('success');
            this.getElement().classList.add('error');
        }
    }

    public checkSize(currentLength: number) : string {

        // Initialise error messages
        let minimumSizeMessage = 'The ' + this.getName() + ' field doesn\'t contain enough characters.';
        let maximumSizeMessage = 'The ' + this.getName() + ' field contains too many characters.';

        // Check size length
        if (currentLength < this.minimumSize) {
            return minimumSizeMessage;
        }
        else if (currentLength > this.maximumSize) {
            return maximumSizeMessage;
        }
        else {
            return null;
        }
    }

    public checkAllowedCharacters(inputValue: string) : string {

        // Get all invalid characters
        let invalidCharacters = [];
        inputValue.split('').forEach((character) => {
            if (!this.allowedCharacters.test(character) && invalidCharacters.indexOf(character) === -1) {
                invalidCharacters.push(character);
            }
        });

        // Check if error message should be displayed
        if (invalidCharacters.length > 0) {
            return 'The following characters are invalid: ' + invalidCharacters.join(', ') + '.'
        }
        else {
            return null
        }
    }

    // Getters and setters
    public getMinimumSize() : number {
        return this.minimumSize;
    }

    public getMaximumSize() : number {
        return this.maximumSize;
    }

    public getAllowedCharacters() : RegExp {
        return this.allowedCharacters;
    }
}