import {DatabaseEntity} from "./DatabaseEntity";

export class DatabaseEntityCollection {

    private readonly databaseEntities: DatabaseEntity[];
    private readonly firstEntity: DatabaseEntity;
    private readonly lastEntity: DatabaseEntity;

    public constructor(entities: DatabaseEntity[]) {

        this.databaseEntities = entities;
        this.firstEntity = this.databaseEntities[0];
        this.lastEntity = this.databaseEntities[this.databaseEntities.length - 1];
    }

    // Getters and setters
    public getEntities() : DatabaseEntity[] {
        return this.databaseEntities;
    }

    public getFirstEntity() : DatabaseEntity {
        return this.firstEntity;
    }

    public getFirstId() : number {
        return this.firstEntity.getId();
    }

    public getLastEntity() : DatabaseEntity {
        return this.lastEntity;
    }

    public getLastId() : number {
        return this.lastEntity.getId();
    }
}