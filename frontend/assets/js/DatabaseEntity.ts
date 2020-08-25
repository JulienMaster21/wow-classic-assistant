export class DatabaseEntity {

    private id: number;
    private htmlString: string;

    public constructor(id: number,
                       htmlString: string) {

        this.id = id;
        this.htmlString = htmlString;
    }

    // Getters and setters
    public getId() : number {
        return this.id;
    }

    public setId(id: number): void {
        this.id = id;
    }

    public getHtmlString() : string {
        return this.htmlString;
    }

    public setHtmlString(htmlString: string) : void {
        this.htmlString = htmlString;
    }
}