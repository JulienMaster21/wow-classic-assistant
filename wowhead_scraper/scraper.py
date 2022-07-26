import os
import re
import sys

import requests
import shutil
import time

from bs4 import BeautifulSoup
from csv import DictReader, DictWriter, reader
from json import loads
from wowhead_scraper.exceptions import InvalidSiteVersionException
from wowhead_scraper.json_fixer import find_icons, fix_data_key, fix_json, is_url_format
from wowhead_scraper.logger import Logger
from wowhead_scraper.time import log_time


class WowheadScraper:

    def __init__(self, site_version):

        # Init logger
        self.logger = Logger()

        # Setup global exception handler
        sys.excepthook = self.logger.exception

        # Determine site version
        if site_version in ("classic", "old"):
            self.domain = "classic"
        elif site_version == ("retail", "standard", "live"):
            self.domain = "www"
        elif site_version in ("ptr", "public test realm"):
            self.domain = "ptr"
        else:
            raise InvalidSiteVersionException
        self.logger.log(f"Scraper is using domain: {self.domain}")

        # Define constants
        self.wowhead_url = f"https://{self.domain}.wowhead.com"
        self.logger.log(f"The url to wowhead is: {self.wowhead_url}")
        self.sources = self.read_json_file("wowhead_scraper/json_data/sources")
        self.item_slots = self.read_json_file("wowhead_scraper/json_data/item_slots")
        self.validation_rules = self.read_json_file("wowhead_scraper/json_data/validation_rules")

    def get_all(self):

        professions = self.get_profession_data()["professions"]
        professions["Enchanting"]["enchantments"] = self.get_enchantments()["enchantments"]
        professions["recipe_specialisations"] = self.get_specialisation_recipes()["recipe_specialisations"]
        craftable_items = self.get_craftable_items()["craftable_items"]
        for profession in craftable_items:
            professions[profession]["craftable_items"] = craftable_items[profession]

        result = {
            "professions": professions,
            "locations": self.get_locations()["locations"],
            "vendors": self.get_vendors()["vendors"],
            "reagents": self.get_reagents()["reagents"],
        }

        return result

    def scrape_all(self):

        # Log starting message
        self.logger.log("Starting...")

        # Clear data directory
        clear_data_results = self.clear_data()
        log_time(self.logger,
                 clear_data_results["start_time"],
                 clear_data_results["end_time"],
                 "clear",
                 "data cache")

        # Prepare data directory for scraping
        prepare_data_results = self.prepare_data_for_scraping()
        log_time(self.logger,
                 prepare_data_results["start_time"],
                 prepare_data_results["end_time"],
                 "prepare",
                 "data cache")

        # Get main professions from professions index
        # Get secondary professions from secondary skills
        professions_results = self.scrape_professions()
        log_time(self.logger,
                 professions_results["start_time"],
                 professions_results["end_time"],
                 "scrape",
                 "professions")

        # Get locations from index
        locations_results = self.scrape_locations()
        log_time(self.logger,
                 locations_results["start_time"],
                 locations_results["end_time"],
                 "scrape",
                 "locations")

        # Get vendors from npcs search page with vendors filter
        vendors_results = self.scrape_vendors()
        log_time(self.logger,
                 vendors_results["start_time"],
                 vendors_results["end_time"],
                 "scrape",
                 "vendors")

        # Get reagents from items search page with reagent filter
        reagents_results = self.scrape_reagents()
        log_time(self.logger,
                 reagents_results["start_time"],
                 reagents_results["end_time"],
                 "scrape",
                 "reagents")

        # Get enchantments from spells search page with enchantment and has reagents filter
        enchantments_results = self.scrape_enchantments()
        log_time(self.logger,
                 enchantments_results["start_time"],
                 enchantments_results["end_time"],
                 "scrape",
                 "enchantments")

        # Get crafted items from items search page with crafted by profession filter for each profession
        # Also include >= 0 sale price filter to include sale price in results
        craftable_items_results = self.scrape_craftable_items()
        log_time(self.logger,
                 craftable_items_results["start_time"],
                 craftable_items_results["end_time"],
                 "scrape",
                 "craftable items")

        # Get profession data from profession page for each profession
        # Including: trainers, recipes, and recipe items
        profession_data_results = self.scrape_profession_data()
        log_time(self.logger,
                 profession_data_results["start_time"],
                 profession_data_results["end_time"],
                 "scrape",
                 "profession data")

        # Get recipe specialisations
        recipe_specialisation_results = self.scrape_specialisation_recipes()
        log_time(self.logger,
                 recipe_specialisation_results["start_time"],
                 recipe_specialisation_results["end_time"],
                 "scrape",
                 "profession specialisations")

        # Check if the scraped data meets the database schema
        check_data_results = self.check_data()
        log_time(self.logger,
                 check_data_results["start_time"],
                 check_data_results["end_time"],
                 "check",
                 "scraped data")

        # Log total time
        log_time(self.logger,
                 professions_results["start_time"],
                 check_data_results["end_time"],
                 "scrape",
                 "scraped data")

        results = {
            "start_time": professions_results["start_time"],
            "end_time": check_data_results["end_time"]
        }

        return results

    def clear_data(self):

        # Start timer
        start_time = time.time()

        # Check if data directory exists
        if not os.path.isdir("data"):
            os.makedirs("data")

        # Delete all files and directories
        self.clear_directory("data/")

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    # Create source file
    def prepare_data_for_scraping(self):

        # Start timer
        start_time = time.time()

        # Create sources psv file
        sources = tuple([{"name": source} for source in self.sources.values()])
        self.create_psv_file("data/source",
                             self.get_header_names(self.domain, "source"),
                             sources)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_main_professions(self):

        return self.scrape_table_page("/professions",
                                      ("skills",),
                                      ("main_professions",))

    def get_secondary_professions(self):

        return self.scrape_table_page("/secondary-skills",
                                      ("skills",),
                                      ("secondary_professions",))

    def get_professions(self):

        main_professions_data = self.get_main_professions()["main_professions"]
        secondary_profession_data = self.get_secondary_professions()["secondary_professions"]
        known_secondary_professions = ("Cooking",
                                       "First Aid",
                                       "Fishing")
        secondary_profession_data = list(filter(
            lambda elem: elem["name"] in known_secondary_professions,
            secondary_profession_data))

        return {
            "professions": tuple(main_professions_data + secondary_profession_data)
        }

    # Create profession file
    # Create specialisation file
    def scrape_professions(self):

        # Start timer
        start_time = time.time()

        # Scrape main professions
        main_professions_data = self.get_main_professions()["main_professions"]
        main_professions = []
        specialisations = []
        for profession in main_professions_data:

            name = profession["name"].rstrip()
            main_professions.append({
                "name": name,
                "wowhead_id": profession["id"],
                "wowhead_link_url": "{wowhead_url}/{name}".format(
                    wowhead_url=self.wowhead_url,
                    name=(((profession["name"]
                            .lower())
                           .replace(" ", "-"))
                          .replace("'", "''"))
                ),
                "icon_link_url": self.format_icon_link(profession["icon"]),
                "is_main_profession": True
            })

            # Find specialisations
            profession_spells_data = self.scrape_listview_page("spells",
                                                               "specialisations",
                                                               "/spells/professions/{name}?filter=20;2;0".format(
                                                                   name=(((profession["name"]
                                                                           .lower())
                                                                          .replace(" ", "-"))
                                                                         .replace("'", "''"))
                                                               ))["specialisations"]
            for spell in profession_spells_data:

                if spell["name"] != profession["name"] and \
                        spell.get("specialization", None) is not None:
                    name = spell["name"].rstrip()
                    specialisations.append({
                        "name": name,
                        "wowhead_id": spell["id"],
                        "wowhead_link_url": "{wowhead_url}/spell={id}/{name}".format(
                            wowhead_url=self.wowhead_url,
                            id=spell["id"],
                            name=(((spell["name"]
                                    .lower())
                                   .replace(" ", "-"))
                                  .replace("'", "''"))
                        ),
                        "profession_name": profession["name"]
                    })

        # Turn main professions and specialisations list into tuple
        main_professions = tuple(main_professions)
        specialisations = tuple(specialisations)

        # Scrape secondary professions
        secondary_professions_data = self.get_secondary_professions()["secondary_professions"]
        known_secondary_professions = ("Cooking",
                                       "First Aid",
                                       "Fishing")
        secondary_professions = []
        for profession in secondary_professions_data:

            if profession["name"] in known_secondary_professions:
                name = ((profession["name"]
                         .rstrip())
                        .replace("'", "''"))
                secondary_professions.append({
                    "name": name,
                    "wowhead_id": profession["id"],
                    "wowhead_link_url": "{wowhead_url}/{name}".format(
                        wowhead_url=self.wowhead_url,
                        name=(((profession["name"]
                                .lower())
                               .replace(" ", "-"))
                              .replace("'", "''"))
                    ),
                    "icon_link_url": self.format_icon_link(profession["icon"]),
                    "is_main_profession": False
                })

        # Turn secondary professions list into tuple
        secondary_professions = tuple(secondary_professions)

        # Create professions psv file
        professions = main_professions + secondary_professions
        self.create_psv_file("data/profession",
                             self.get_header_names(self.domain, "profession"),
                             professions)

        # Create specialisations psv file
        self.create_psv_file("data/specialisation",
                             self.get_header_names(self.domain, "specialisation"),
                             specialisations)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_locations(self):

        return self.scrape_table_page("/zones",
                                      ("zones",),
                                      ("locations",))

    # Create location file
    def scrape_locations(self):

        # Start timer
        start_time = time.time()

        # Scrape locations
        locations_data = self.get_locations()["locations"]

        location_types = {
            0: "Safe instance",
            1: "Zone",
            2: "Dungeon",
            3: "Raid",
            6: "Battleground"
        }

        faction_states = {
            0: "Alliance",
            1: "Horde",
            2: "Contested",
            4: "PvP"
        }

        locations = []
        for location in locations_data:

            # Determine location category index
            location["category"] = int(location["category"])
            if location["category"] == -1:
                location_category_index = 0
            # 0 is a location in the Eastern Kingdom and 1 is a location in Kalimdor
            elif location["category"] == 0 or location["category"] == 1:
                location_category_index = 1
            else:
                location_category_index = location["category"]

            # Format data
            name = location["name"].rstrip()
            locations.append({
                "name": name,
                "wowhead_id": location["id"],
                "wowhead_link_url": "{wowhead_url}/{name}".format(
                    wowhead_url=self.wowhead_url,
                    name=(((location["name"]
                            .lower())
                           .replace("'", ""))
                          .replace(" ", "-"))
                ),
                "location_type": location_types[location_category_index],
                "faction_status": faction_states[location["territory"]],
                "required_level": location.get("reqlevel", None),
                "minimum_level": location.get("minlevel", None),
                "maximum_level": location.get("maxlevel", None)
            })

        # Turn locations list into tuple
        locations = tuple(locations)

        # Create locations psv file
        self.create_psv_file("data/location",
                             self.get_header_names(self.domain, "location"),
                             locations)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_vendors(self):

        return self.scrape_table_page("/npcs?filter=29;1;0",
                                      ("npcs",),
                                      ("vendors",))

    # Create vendor file
    # Create location_vendor file
    def scrape_vendors(self):

        # Start timer
        start_time = time.time()

        # Read locations
        known_locations = self.get_data_tuple_from_psv("data/location")

        # Scrape vendors
        vendors_data = self.get_vendors()["vendors"]
        vendors = []
        vendor_location = []
        for vendor in vendors_data:

            # Get reactions
            reactions = self.process_reactions(vendor)

            # Find location names
            locations = []
            for location in vendor.get("location", []):

                for known_location in known_locations:

                    if int(known_location["wowhead_id"]) == location:
                        locations.append(known_location["name"])

            locations = tuple(locations)

            # Format data
            name = vendor["name"].rstrip()
            vendors.append({
                "name": name,
                "wowhead_id": vendor["id"],
                "wowhead_link_url": "{wowhead_url}/npc={id}/{name}".format(
                    wowhead_url=self.wowhead_url,
                    id=vendor["id"],
                    name=(((vendor["name"]
                            .lower())
                           .replace("'", ""))
                          .replace(" ", "-"))
                ),
                "reaction_to_alliance": reactions["alliance"],
                "reaction_to_horde": reactions["horde"]
            })

            for location in locations:
                vendor_location.append({
                    "vendor_name": vendor["name"],
                    "location_name": location
                })

        vendors = tuple(vendors)
        vendor_location = tuple(vendor_location)

        # Create vendors psv file
        self.create_psv_file("data/vendor",
                             self.get_header_names(self.domain, "vendor"),
                             vendors)

        # Create vendor location psv file
        self.create_psv_file("data/location_vendor",
                             self.get_header_names(self.domain, "location_vendor"),
                             vendor_location)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_reagents(self):

        return self.scrape_listview_page("items",
                                         "reagents",
                                         "/items?filter=87;11;0")

    # Create reagent file
    # Create reagent_source file
    # Create reagent_vendor file
    def scrape_reagents(self):

        # Start timer
        start_time = time.time()

        result = self.get_reagents()
        reagents_data = result["reagents"]
        icons = result["icons"]
        reagents = []
        reagent_sources = []
        reagent_vendors = ()
        for index, reagent in enumerate(reagents_data):

            # Log status update
            self.logger.log(f"Getting reagent {index + 1} of {len(reagents_data)}.")

            # If reagent is buyable, then get vendors
            if 5 in reagent.get("source", []):
                reagent_details = self.scrape_reagent_details(reagent["name"],
                                                              "/item={id}/{name}".format(
                                                                  wowhead_url=self.wowhead_url,
                                                                  id=reagent["id"],
                                                                  name=(((reagent["name"]
                                                                          .lower())
                                                                         .replace("'", ""))
                                                                        .replace(" ", "-"))
                                                              ))

                reagent_vendors += reagent_details["reagent_vendors"]

            # Format data
            name = reagent["name"].rstrip()
            reagents.append({
                "name": name,
                "wowhead_id": reagent["id"],
                "wowhead_link_url": "{wowhead_url}/item={id}/{name}".format(
                    wowhead_url=self.wowhead_url,
                    id=reagent["id"],
                    name=(((reagent["name"]
                            .lower())
                           .replace("'", ""))
                          .replace(" ", "-"))
                ),
                "icon_link_url": self.format_icon_link(icons[reagent["name"].replace("''", "'")])
            })

            # Format sources
            for source in reagent.get("source", []):
                source = str(source)
                reagent_sources.append({
                    "reagent_name": reagent["name"],
                    "source_name": self.sources[source],
                })

        reagents = tuple(reagents)
        reagent_sources = tuple(reagent_sources)

        # Create reagents psv file
        self.create_psv_file("data/reagent",
                             self.get_header_names(self.domain, "reagent"),
                             reagents)

        # Create reagent sources psv file
        self.create_psv_file("data/reagent_source",
                             self.get_header_names(self.domain, "reagent_source"),
                             reagent_sources)

        self.create_psv_file("data/reagent_vendor",
                             self.get_header_names(self.domain, "reagent_vendor"),
                             reagent_vendors)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def scrape_reagent_details(self, reagent_name: str, relative_reagent_link: str):

        # Get vendors of reagent
        reagent_vendors = []
        sold_by_data = self.scrape_table_page(relative_reagent_link,
                                              ("sold-by",),
                                              ("sold_by",)
                                              )["sold_by"]
        for vendor in sold_by_data:
            reagent_vendors.append({
                "vendor_name": vendor["name"],
                "reagent_name": reagent_name,
                "buy_price": vendor["cost"][0]
            })

        reagent_vendors = tuple(reagent_vendors)

        results = {
            "reagent_vendors": reagent_vendors
        }

        return results

    # See https://classic.wowhead.com/spells/professions/enchanting?filter=20:109;1:53;0:0
    def get_enchantments(self):

        return self.scrape_listview_page("spells",
                                         "enchantments",
                                         "/spells/professions/enchanting?filter=20:109;1:53;0:0")

    # Create enchantment file
    def scrape_enchantments(self):

        # Start timer
        start_time = time.time()

        enchantment_data = self.get_enchantments()["enchantments"]

        enchantments = []
        for enchantment in enchantment_data:
            name = enchantment["name"].rstrip()
            enchantments.append({
                "name": name,
                "wowhead_id": enchantment["id"],
                "wowhead_link_url": "{wowhead_url}/spell={id}/{name}".format(
                    wowhead_url=self.wowhead_url,
                    id=enchantment["id"],
                    name=((((enchantment["name"]
                             .lower())
                            .replace(" - ", " "))
                           .replace(" ", "-"))
                          .replace("'", "''"))
                ),
                "icon_link_url": self.format_icon_link("spell_holy_greaterheal"),
                "item_category": ((enchantment["name"]
                                   .replace("Enchant ", ""))
                                  .split(" - ")
                                  )[0]
            })

        enchantments = tuple(enchantments)

        # Create enchantment psv file
        self.create_psv_file("data/enchantment",
                             self.get_header_names(self.domain, "enchantment"),
                             enchantments)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_craftable_items(self):

        professions = self.get_data_tuple_from_psv("data/profession")

        craftable_items = {}
        icons = {}
        filter_ids = {
            "Alchemy": 1,
            "Blacksmithing": 2,
            "Cooking": 3,
            "Enchanting": 4,
            "Engineering": 5,
            "First Aid": 6,
            "Leatherworking": 8,
            "Mining": 9,
            "Tailoring": 10
        }
        for profession in professions:

            filter_id = filter_ids.get(profession["name"], None)
            if filter_id is None:
                continue
            else:
                result = self.scrape_listview_page("items",
                                                   "craftable_items",
                                                   f"/items?filter=86:64;{filter_id}:2;0:0")
                craftable_items[profession["name"]] = result["craftable_items"]
                icons.update(result["icons"])

        return {
            "craftable_items": craftable_items,
            "icons": icons
        }

    # Create craftable_item file
    def scrape_craftable_items(self):

        # Start timer
        start_time = time.time()

        result = self.get_craftable_items()
        craftable_items_data = result["craftable_items"]
        icons = result["icons"]

        craftable_items = []
        craftable_item_ids = []
        for profession_name in craftable_items_data:

            for craftable_item in craftable_items_data[profession_name]:

                # Check for duplicate items
                if craftable_item["id"] in craftable_item_ids:
                    continue

                # Process icon link url
                # icon = craftable_item.get("sourcemore", [{}])[0].get("icon", None)
                # icon_link_url = None if icon is None else self.format_icon_link(icon)

                # Add data to lists
                craftable_item_ids.append(craftable_item["id"])

                name = craftable_item["name"].rstrip()
                slot = str(craftable_item["slot"])
                craftable_items.append({
                    "name": name,
                    "wowhead_id": craftable_item["id"],
                    "wowhead_link_url": "{wowhead_url}/item={id}/{name}".format(
                        wowhead_url=self.wowhead_url,
                        id=craftable_item["id"],
                        name=(((craftable_item["name"]
                                .lower())
                               .replace(" ", "-"))
                              .replace("'", "''"))
                    ),
                    "icon_link_url": self.format_icon_link(icons[craftable_item["name"].replace("''", "'")]),
                    "item_slot": self.item_slots[slot],
                    "sell_price": craftable_item.get("sellprice", None)
                })

        craftable_items = tuple(craftable_items)

        # Create craftable item psv file
        self.create_psv_file("data/craftable_item",
                             self.get_header_names(self.domain, "craftable_item"),
                             craftable_items)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_profession_data(self):

        professions = self.get_data_tuple_from_psv("data/profession")

        professions_data = {}
        for profession in professions:
            professions_data[profession["name"]] = self.scrape_table_page(profession["wowhead_link_url"]
                                                                          .replace(self.wowhead_url, ""),
                                                                          ("recipes",
                                                                           "recipe-items",
                                                                           "crafted-items",
                                                                           "spells",
                                                                           "trainers"),
                                                                          ("recipes",
                                                                           "recipe_items",
                                                                           "crafted_items",
                                                                           "spells",
                                                                           "trainers"))

        profession_data_dictionary = {
            "professions": professions_data
        }

        return profession_data_dictionary

    # Create trainer file
    # Create profession_trainer file
    # Create recipe_item
    # Create recipe file
    # Create recipe_specialisation
    # Create reagent_recipe
    # Create recipe_trainer
    def scrape_profession_data(self):

        # Start timer
        start_time = time.time()

        profession_data = self.get_profession_data()["professions"]
        locations = self.get_data_tuple_from_psv("data/location")
        enchantments = self.get_data_tuple_from_psv("data/enchantment")
        craftable_items = self.get_data_tuple_from_psv("data/craftable_item")
        reagents = self.get_data_tuple_from_psv("data/reagent")

        trainers = []
        profession_trainers = []
        recipe_items = []
        recipes = []
        reagent_recipes = []
        recipe_trainers = []
        for profession_name in profession_data:

            for index, trainer in enumerate(profession_data[profession_name].get("trainers", [])):

                # Log status update
                self.logger.log(f"Getting {profession_name} trainer {index + 1} of "
                                f"{len(profession_data[profession_name]['trainers'])}.")

                # Get reactions
                reactions = self.process_reactions(trainer)

                # Get location
                location = {
                    "name": "Unknown"
                }
                if trainer.get("location", None) is not None:
                    for known_location in locations:

                        if int(known_location["wowhead_id"]) == trainer["location"][0]:
                            location = known_location

                name = trainer["name"].rstrip()
                trainers.append({
                    "name": name,
                    "wowhead_id": trainer["id"],
                    "wowhead_link_url": "{wowhead_url}/npc={id}/{name}".format(
                        wowhead_url=self.wowhead_url,
                        id=trainer["id"],
                        name=(((trainer["name"]
                                .lower())
                               .replace(" ", "-"))
                              .replace("'", "''"))
                    ),
                    "reaction_to_alliance": reactions["alliance"],
                    "reaction_to_horde": reactions["horde"],
                    "location_name": location["name"]
                })

                profession_trainers.append({
                    "trainer_name": trainer["name"],
                    "profession_name": profession_name
                })

                current_trainer_recipes = self.scrape_table_page("/npc={id}/{name}".format(
                    wowhead_url=self.wowhead_url,
                    id=trainer["id"],
                    name=(((location["name"]
                            .lower())
                           .replace(" ", "-"))
                          .replace("'", "''"))
                ),
                    ("teaches-recipe",),
                    ("recipes",)
                )["recipes"]
                for recipe in current_trainer_recipes:

                    if recipe.get("reagents", None) is not None:
                        recipe_trainers.append({
                            "recipe_name": recipe["name"],
                            "trainer_name": trainer["name"]
                        })

        trainers = tuple(trainers)
        profession_trainers = tuple(profession_trainers)
        recipe_trainers = tuple(recipe_trainers)

        for profession_name in profession_data:

            icons = profession_data[profession_name].get("icons", {})
            for recipe_item in profession_data[profession_name].get("recipe_items", []):
                name = recipe_item["name"].rstrip()
                icon_name = icons[((name
                                    .replace("''", "'"))
                                   .replace("`", '"'))]
                icon_link_url = self.format_icon_link(icon_name)
                recipe_items.append({
                    "name": name,
                    "wowhead_id": recipe_item["id"],
                    "wowhead_link_url": "{wowhead_url}/item={id}/{name}".format(
                        wowhead_url=self.wowhead_url,
                        id=recipe_item["id"],
                        name=((((((recipe_item["name"]
                                   .lower())
                                  .replace(":", ""))
                                 .replace(" - ", " "))
                                .replace(" ", "-"))
                               .replace("'", "''"))
                              .replace("`", '"'))
                    ),
                    "icon_link_url": icon_link_url,
                    "required_skill_level": recipe_item["skill"],
                    "profession_name": profession_name
                })

        recipe_items = tuple(recipe_items)

        unknown_reagents = []
        unknown_reagent_ids = {}
        for profession_name in profession_data:

            icons = profession_data[profession_name].get("icons", {})
            for recipe in profession_data[profession_name].get("recipes", []):

                # Get skill categories
                difficulty_data = self.process_difficulty(recipe)

                # Check if recipe is trained by recipe item
                recipe_item_name = None
                for recipe_item in recipe_items:

                    recipe_item_name_without_prefix = recipe_item["name"].split(": ")[1] \
                        if len(recipe_item["name"].split(": ")) > 1 \
                        else recipe_item["name"]
                    if recipe_item_name_without_prefix == recipe["name"]:
                        recipe_item_name = recipe_item["name"]

                # Check if recipe produces a craftable item or an enchantment
                enchantment_name = None
                for enchantment in enchantments:

                    if enchantment["name"] == recipe["name"]:
                        enchantment_name = enchantment["name"]

                craftable_item_name = None
                for craftable_item in craftable_items:

                    if craftable_item["name"] == recipe["name"]:
                        craftable_item_name = craftable_item["name"]

                name = recipe["name"].rstrip()
                icon_name = icons[((name
                                    .replace("''", "'"))
                                   .replace("`", '"'))]
                icon_link_url = self.format_icon_link(icon_name)
                minimum_amount_created = recipe.get("creates", [1, 1, 1])[1]
                if minimum_amount_created < 1:
                    minimum_amount_created = 1
                maximum_amount_created = recipe.get("creates", [1, 1, 1])[2]
                if maximum_amount_created < 1:
                    maximum_amount_created = minimum_amount_created
                recipes.append({
                    "name": f"{name} - {profession_name}",
                    "wowhead_id": recipe["id"],
                    "wowhead_link_url": "{wowhead_url}/spell={id}/{name}".format(
                        wowhead_url=self.wowhead_url,
                        id=recipe["id"],
                        name=(((((name
                                  .lower())
                                 .replace(" - ", " "))
                                .replace(" ", "-"))
                               .replace("'", ""))
                              .replace("`", ""))
                    ),
                    "icon_link_url": icon_link_url,
                    "difficulty_requirement": difficulty_data["requirement"],
                    "difficulty_category_1": difficulty_data["categories"][0],
                    "difficulty_category_2": difficulty_data["categories"][1],
                    "difficulty_category_3": difficulty_data["categories"][2],
                    "difficulty_category_4": difficulty_data["categories"][3],
                    "minimum_amount_created": minimum_amount_created,
                    "maximum_amount_created": maximum_amount_created,
                    "training_cost": recipe.get("trainingcost", None),
                    "profession_name": profession_name,
                    "recipe_item_name": recipe_item_name,
                    "craftable_item_name": craftable_item_name,
                    "enchantment_name": enchantment_name
                })

                for reagent in recipe.get("reagents", []):

                    reagent_name = None
                    for known_reagent in reagents:

                        if int(known_reagent["wowhead_id"]) == reagent[0]:
                            reagent_name = known_reagent["name"]

                    # If reagent is unknown, add it to reagents
                    if reagent_name is None and \
                            unknown_reagent_ids.get(reagent[0], None) is None:

                        # Get unknown reagent
                        unknown_reagent_data = self.scrape_details_page("/item={id}".format(
                            id=reagent[0]
                        ),
                            reagent[0])

                        # Set reagent name
                        reagent_name = unknown_reagent_data["name"]

                        # Add new reagent data
                        unknown_reagents.append({
                            "name": reagent_name,
                            "wowhead_id": reagent[0],
                            "wowhead_link_url": "{wowhead_url}/item={id}/{name}".format(
                                wowhead_url=self.wowhead_url,
                                id=reagent[0],
                                name=(((((reagent_name
                                          .lower())
                                         .replace("[", ""))
                                        .replace("]", ""))
                                       .replace("'", ""))
                                      .replace(" ", "-"))
                            ),
                            "icon_link_url": unknown_reagent_data["icon_link_url"]
                        })

                        unknown_reagent_ids[reagent[0]] = reagent_name

                    elif reagent_name is None:
                        reagent_name = unknown_reagent_ids.get(reagent[0], None)

                    reagent_recipes.append({
                        "recipe_name": recipe["name"],
                        "reagent_name": reagent_name,
                        "amount": reagent[1]
                    })

        unknown_reagents = tuple(unknown_reagents)

        # Append unknown reagent data
        self.append_data_to_psv_file("data/reagent",
                                     self.get_header_names(self.domain, "reagent"),
                                     unknown_reagents)

        recipes = tuple(recipes)
        reagent_recipes = tuple(reagent_recipes)

        # Create trainer psv file
        self.create_psv_file("data/trainer",
                             self.get_header_names(self.domain, "trainer"),
                             trainers)

        # Create profession trainer psv file
        self.create_psv_file("data/profession_trainer",
                             self.get_header_names(self.domain, "profession_trainer"),
                             profession_trainers)

        # Create recipe item psv file
        self.create_psv_file("data/recipe_item",
                             self.get_header_names(self.domain, "recipe_item"),
                             recipe_items)

        # Create recipe psv file
        self.create_psv_file("data/recipe",
                             self.get_header_names(self.domain, "recipe"),
                             recipes)

        # Create reagent recipe psv file
        self.create_psv_file("data/reagent_recipe",
                             self.get_header_names(self.domain, "reagent_recipe"),
                             reagent_recipes)

        # Create recipe trainer psv file
        self.create_psv_file("data/recipe_trainer",
                             self.get_header_names(self.domain, "recipe_trainer"),
                             recipe_trainers)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    def get_specialisation_recipes(self):

        specialisations = self.get_data_tuple_from_psv("data/specialisation")

        recipe_specialisations = {}

        for specialisation in specialisations:
            recipes = self.scrape_listview_page("spells",
                                                "recipes",
                                                "/spells/professions/{profession}/{specialisation}"
                                                "?filter=20;1;0".format(
                                                    profession=((specialisation["profession_name"]
                                                                 .lower())
                                                                .replace(" ", "-")),
                                                    specialisation=((specialisation["name"]
                                                                     .lower())
                                                                    .replace(" ", "-"))
                                                ))["recipes"]

            recipe_specialisations[specialisation["name"]] = recipes

        return {
            "recipe_specialisations": recipe_specialisations
        }

    def scrape_specialisation_recipes(self):

        # Start timer
        start_time = time.time()

        recipe_specialisation_data = self.get_specialisation_recipes()["recipe_specialisations"]

        recipe_specialisations = []
        for specialisation in recipe_specialisation_data:

            # TODO Wait for other specialisation endpoints to become available
            if "Engineer" in specialisation:
                for recipe in recipe_specialisation_data[specialisation]:
                    recipe_specialisations.append({
                        "recipe_name": recipe["name"],
                        "specialisation_name": specialisation
                    })

        recipe_specialisations = tuple(recipe_specialisations)

        # Create recipe specialisation psv file
        self.create_psv_file("data/recipe_specialisation",
                             self.get_header_names(self.domain, "recipe_specialisation"),
                             recipe_specialisations)

        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    # TODO Write check data method
    def check_data(self):

        # Start timer
        start_time = time.time()



        # End timer
        end_time = time.time()

        results = {
            "start_time": start_time,
            "end_time": end_time
        }

        return results

    # Domain logic
    def process_reactions(self, npc_dict: dict):

        reaction_types = {
            0: "Neutral",
            1: "Friendly",
            2: "Hostile",
            3: "Unknown"
        }

        if npc_dict.get("react") is None:
            alliance_reaction_index = 3
            horde_reaction_index = 3
        else:
            # Alliance
            if npc_dict["react"][0] is None:
                alliance_reaction_index = 3
            else:
                npc_dict["react"][0] = int(npc_dict["react"][0])
                if npc_dict["react"][0] == -1:
                    alliance_reaction_index = 2
                else:
                    alliance_reaction_index = npc_dict["react"][0]

            # Horde
            if npc_dict["react"][1] is None:
                horde_reaction_index = 3
            else:
                npc_dict["react"][1] = int(npc_dict["react"][1])
                if npc_dict["react"][1] == -1:
                    horde_reaction_index = 2
                else:
                    horde_reaction_index = npc_dict["react"][1]

        reactions = {
            "alliance": reaction_types[alliance_reaction_index],
            "horde": reaction_types[horde_reaction_index]
        }

        return reactions

    def process_difficulty(self, recipe_dict: dict):

        # Get difficulty requirement
        requirement = recipe_dict.get("learnedat", 1)

        # Get categories
        # Should always be an array of 4 integers
        categories = recipe_dict.get("colors", [1, 1, 1, 1])

        # Check if categories meet the minimum requirement
        if categories[0] < requirement:
            categories[0] = requirement

        if categories[1] < categories[0]:
            categories[1] = categories[0]

        if categories[2] < categories[1]:
            categories[2] = categories[1]

        if categories[3] < categories[2]:
            categories[3] = categories[2]

        categories = tuple(categories)

        return {
            "requirement": requirement,
            "categories": categories
        }

    # Text format functions
    def format_icon_link(self, name):

        return f"https://wow.zamimg.com/images/wow/icons/small/{name}.jpg"

    # IO functions
    def remove_file(self, file_path: str):

        os.remove(file_path)

    def clear_directory(self, directory_path: str):

        for fileOrDir in os.listdir(directory_path):

            if os.path.isdir(directory_path + fileOrDir):
                shutil.rmtree(directory_path + fileOrDir)
            else:
                self.remove_file(directory_path + fileOrDir)

    def create_psv_file(self, file_path: str, field_names: iter, data_tuple: iter):

        with open(f"{file_path}.psv", "w", newline="") as file:
            writer = DictWriter(file, fieldnames=field_names, delimiter='|', lineterminator='\n')
            writer.writeheader()
            writer.writerows(data_tuple)

    def append_data_to_psv_file(self, file_path: str, field_names: iter, data_tuple: iter):

        with open(f"{file_path}.psv", "a", newline="") as file:
            writer = DictWriter(file, fieldnames=field_names, delimiter='|', lineterminator='\n')
            writer.writerows(data_tuple)

    def get_data_tuple_from_psv(self, file_path: str):

        data = []
        with open(f"{file_path}.psv", newline="") as file:
            reader = DictReader(file, delimiter='|', lineterminator='\n')
            for row in reader:
                data.append(row)

        data = tuple(data)

        return data

    def read_json_file(self, file_path: str):

        with open(f"{file_path}.json", "r", newline="") as file:
            data = file.read()

        json = loads(data)
        return json

    # Page scrape functions
    def scrape_table_page(self, relative_url: str, table_ids: tuple, data_keys: tuple):

        url = f"{self.wowhead_url}{relative_url}"
        html = self.get_page(url)
        html = BeautifulSoup(html, "html.parser")
        script_tags = html.find_all("script", text=True)
        data = {}
        icons_found = False
        for index, table_id in enumerate(table_ids):
            for tag in script_tags:

                tag_text = tag.contents[0]
                tag_text = fix_data_key(tag_text)
                if re.search(fr"new Listview(?:[^~]*?{table_id})[^~]*?\"data\": ([^;]*])[^;]*}}\);",
                             tag_text,
                             re.MULTILINE):
                    if not icons_found:
                        data["icons"] = {}
                        icon_jsons = re.findall(r"WH\.Gatherer.addData\(\d+, \d+, ([^;]+)\);",
                                                tag_text,
                                                re.MULTILINE)
                        if icon_jsons is not None:
                            for icon_json in icon_jsons:
                                data["icons"].update(find_icons(icon_json))
                        icons_found = True
                    json_data = re.search(fr"new Listview(?:[^~]*?{table_id})[^~]*?\"data\": ([^;]*])[^;]*}}\);",
                                          tag_text,
                                          re.MULTILINE).group(1)
                    data[data_keys[index]] = fix_json(json_data)

        return data

    def scrape_listview_page(self, listview_name: str, data_key: str, relative_url: str):

        url = f"{self.wowhead_url}{relative_url}"
        html = self.get_page(url)
        html = BeautifulSoup(html, "html.parser")
        script_tags = html.find_all("script", text=True)
        data = {}
        for tag in script_tags:

            tag_text = tag.contents[0]
            if re.search(fr"var listview{listview_name} = ([^;]+);",
                         tag_text,
                         re.MULTILINE):
                data["icons"] = {}
                icon_jsons = re.findall(r"WH\.Gatherer.addData\(\d+, \d+, ([^;]+)\);",
                                        tag_text,
                                        re.MULTILINE)
                if icon_jsons is not None:
                    for icon_json in icon_jsons:
                        data["icons"].update(find_icons(icon_json))
                json_data = re.search(fr"var listview{listview_name} = ([^;]+);",
                                      tag_text,
                                      re.MULTILINE).group(1)
                data[data_key] = fix_json(json_data)

        return data

    def scrape_details_page(self, relative_url: str, wowhead_id: int):

        url = f"{self.wowhead_url}{relative_url}"
        html = self.get_page(url)
        html = BeautifulSoup(html, "html.parser")

        # Get name
        name = (((html
                  .find_all(name="h1", class_="heading-size-1"))[0]
                 .get_text())
                .rstrip())

        # Get icon
        script_tags = html.find_all("script", text=True)
        icon_name = None
        for tag in script_tags:

            tag_text = tag.contents[0]
            if re.search(
                    fr"WH\.ge\('ic{str(wowhead_id)}'\)\.appendChild\(Icon\.create\('([a-z0-9_]+)'[{{}}\[\]:\"'`,._\- &a-zA-Z0-9\\/]+\)\);",
                    tag_text,
                    re.MULTILINE):
                icon_name = re.search(
                    fr"WH\.ge\('ic{str(wowhead_id)}'\)\.appendChild\(Icon\.create\('([a-z0-9_]+)'[{{}}\[\]:\"'`,._\- &a-zA-Z0-9\\/]+\)\);",
                    tag_text,
                    re.MULTILINE).group(1)

        return {
            "name": name,
            "icon_link_url": self.format_icon_link(icon_name)
        }

    # Web functions
    def get_page(self, url):

        request = None
        status_is_not_200 = True
        while status_is_not_200:

            # Add 10 second grace period per request, to not overload the wowhead servers
            time.sleep(10)

            # Create request
            headers = {
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                "Accept-Encoding": "gzip, deflate, br",
                "Accept-Language": "en-GB,en;q=0.5",
                "Host": "classic.wowhead.com",
                "Upgrade-Insecure-Requests": "1",
                "User-Agent": "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:87.0) Gecko/20100101 Firefox/87.0"
            }
            request = requests.get(url, headers=headers)

            # If GET request succeeds, then stop trying
            if request.status_code == 200:
                status_is_not_200 = False

        return request.text

    # Utility functions
    def get_header_names(self, version: str, file_path: str):

        data_files = self.validation_rules.get(version, None)
        if data_files is None:
            raise InvalidSiteVersionException

        data_file = data_files.get(file_path, None)
        if data_file is None:
            raise FileNotFoundError

        return tuple(data_file.keys())

    def check_validation_rules(self, version: str):

        validation_succeeded = True
        expected_data_files = self.validation_rules.get(version, None)
        if expected_data_files is None:
            validation_succeeded = False
            self.logger.error(f"There are no validation rules for site version: {version}")
            raise InvalidSiteVersionException

        for expected_file_name in expected_data_files:

            file_rules = expected_data_files.get(expected_file_name, None)
            if file_rules is None:
                self.logger.warning(f"No validation rules were specified for data file: {expected_file_name}")
                continue

            try:
                data = self.get_data_tuple_from_psv(f"data/{expected_file_name}")
            except FileNotFoundError:
                validation_succeeded = False
                self.logger.error(f"Can't find required data file: {expected_file_name}")
                continue

            previous_values = {field_name: [] for field_name in file_rules}

            for index, row in enumerate(data):

                for expected_field_name in file_rules:

                    field_rules = file_rules.get(expected_field_name, None)
                    if field_rules is None:
                        self.logger.warning(f"No validation rules were specified in data file: {expected_file_name} "
                                            f"one row {index + 1} for field {expected_field_name}")
                        continue

                    field_value = row.get(expected_field_name, None)
                    if field_value is None:
                        validation_succeeded = False
                        self.logger.error(f"Expected value wasn't found in data file: {expected_file_name} on row "
                                          f"{index + 1} for field {expected_field_name}")

                    type_rule = field_rules.get("type", None)
                    format_rule = field_rules.get("format", None)
                    minimum_rule = field_rules.get("minimum", None)
                    not_nullable_rule = field_rules.get("not_null", None)
                    enum_rule = field_rules.get("enum", None)
                    unique_rule = field_rules.get("unique", None)

                    if type_rule and \
                       field_value != "":
                        if type_rule == "string":
                            pass
                        if type_rule == "integer":
                            try:
                                field_value_as_int = int(field_value)
                                if field_value_as_int < minimum_rule:
                                    validation_succeeded = False
                                    self.logger.error(f"The value \"{field_value}\" is below the minimum in data file: "
                                                      f"{expected_file_name} on row {index + 1} "
                                                      f"for field {expected_field_name}")
                            except ValueError:
                                validation_succeeded = False
                                self.logger.error(f"The value \"{field_value}\" is not an integer in data file: "
                                                  f"{expected_file_name} on row {index + 1} "
                                                  f"for field {expected_field_name}")
                        if type_rule == "boolean":
                            try:
                                bool(field_value)
                            except ValueError:
                                validation_succeeded = False
                                self.logger.error(f"The value \"{field_value}\" is not a boolean in data file: "
                                                  f"{expected_file_name} on row {index + 1} "
                                                  f"for field {expected_field_name}")

                    if format_rule and \
                       field_value != "":
                        if format_rule == "url":
                            if not is_url_format(field_value):
                                validation_succeeded = False
                                self.logger.error(f"The value \"{field_value}\" is not in the url format in data file: "
                                                  f"{expected_file_name} on row {index + 1} "
                                                  f"for field {expected_field_name}")

                    if not_nullable_rule:
                        if field_value == "":
                            validation_succeeded = False
                            self.logger.error(f"Required value is not present in data file: "
                                              f"{expected_file_name} on row {index + 1} "
                                              f"for field {expected_field_name}")

                    if enum_rule and \
                       field_value != "":
                        try:
                            valid_values = self.read_json_file(f"wowhead_scraper/json_data/{enum_rule}").values()
                            if field_value not in valid_values:
                                validation_succeeded = False
                                self.logger.error(
                                    f"The value \"{field_value}\" is not present in {enum_rule} in data file: "
                                    f"{expected_file_name} on row {index + 1} "
                                    f"for field {expected_field_name}")
                        except FileNotFoundError:
                            self.logger.error(f"The enum type of \"{enum_rule}\" can't be found "
                                              f"in the json_data directory")

                    if unique_rule and \
                       field_value != "":
                        if field_value in previous_values[expected_field_name]:
                            validation_succeeded = False
                            self.logger.error(f"The value \"{field_value}\" is not unique in data file: "
                                              f"{expected_file_name} on row {index + 1} "
                                              f"for field {expected_field_name}")

                    if field_value not in previous_values[expected_field_name]:
                        previous_values[expected_field_name].append(field_value)

        if not validation_succeeded:
            self.logger.error("Data validation failed")
        return validation_succeeded

    def get_data_rows(self, file_path: str):

        data = []
        with open(f"{file_path}.psv", newline="") as file:
            file_reader = reader(file, delimiter='|', lineterminator='\n')
            for row in file_reader:
                data.append(tuple(row))

        data = tuple(data)

        return data
