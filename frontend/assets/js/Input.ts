export abstract class Input {

    private readonly identifier: string;
    private readonly name: string;
    private element: HTMLInputElement;
    private validationPassed: boolean;

    protected constructor(identifier: string,
                          name: string,
                          element: HTMLInputElement = null) {

        this.identifier = identifier;
        this.name = name;
        this.validationPassed = false;
        this.element = element;
    }

    public abstract checkValidation(element: HTMLInputElement);

    // Getters and setters
    public getName() : string {
        return this.name;
    }

    public getIdentifier() : string {
        return this.identifier;
    }

    public getElement() : HTMLInputElement {
        return this.element;
    }

    public setElement(element: HTMLInputElement) : void {
        this.element = element;
    }

    public getValidationPassed() : boolean {
        return this.validationPassed;
    }

    public setValidationPassed(validationPassed: boolean) : void {
        this.validationPassed = validationPassed;
    }
}