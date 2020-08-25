import {TextInput} from "./TextInput";

export class EmailInput extends TextInput {

    constructor(identifier: string,
                name: string,
                minimumSize: number,
                maximumSize: number,
                allowedCharacters: RegExp,
                element: HTMLInputElement = null) {

        super(  identifier,
                name,
                minimumSize,
                maximumSize,
                allowedCharacters,
                element);
    }
}