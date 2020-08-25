import {PasswordInput} from "./PasswordInput";

export class ConfirmPasswordInput extends PasswordInput {

    private readonly secondIdentifier: string;
    private readonly secondName: string;
    private secondElement: HTMLInputElement;

    constructor(identifier: string,
                secondIdentifier: string,
                name: string,
                secondName: string,
                minimumSize: number,
                maximumSize: number,
                allowedCharacters: RegExp,
                element: HTMLInputElement = null,
                secondElement: HTMLInputElement = null) {

        super(  identifier,
                name,
                minimumSize,
                maximumSize,
                allowedCharacters,
                element);

        this.secondIdentifier = secondIdentifier;
        this.secondName = secondName;
        this.secondElement = secondElement;
    }

    // Getters and setters
    public getSecondIdentifier() {
        return this.secondIdentifier;
    }

    public getSecondName() {
        return this.secondName;
    }

    public getSecondElement() {
        return this.secondElement;
    }

    public setSecondElement(secondElement: HTMLInputElement) {
        this.secondElement = secondElement;
    }
}