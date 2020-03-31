from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.common.keys import *
from selenium.common.exceptions import *
import os
import time
import datetime
from wowhead_scraper.exceptions import *


class WowheadScraper:

    def __init__(self):

        # Initialise driver
        self.driver = None

        # Initialise expected row amount dictionary
        self.expected_row_amount = {}

    def start_driver(self):

        # Set headless option
        options = Options()
        options.headless = True
        # Start and return driver
        return webdriver.Firefox(options=options)

    def update_psvs(self):

        print('Starting...')

        # Start recording time
        start_time = time.time()

        # Get professions from index
        self.get_professions()
        end_of_professions_time = self.print_loading_time(start_time, 'professions')

        # Get locations from index
        self.get_locations()
        end_of_locations_time = self.print_loading_time(end_of_professions_time, 'locations')

        # Get vendors from index
        # Link vendor_has_location
        self.get_vendors()
        end_of_vendors_time = self.print_loading_time(end_of_locations_time, 'vendors')

        # Get reagents from index
        self.get_reagents()
        end_of_reagents_time = self.print_loading_time(end_of_vendors_time, 'reagents')

        # Link reagent_has_vendor
        self.get_reagent_details()
        end_of_reagents_details_time = self.print_loading_time(end_of_reagents_time, 'reagent details')

        # Get crafted items for each profession
        self.get_craftable_items()
        end_of_craftable_items_time = self.print_loading_time(end_of_reagents_details_time, 'craftable items')

        # Get profession data for each profession
        # Including: trainers, recipes, and recipe items
        # Link: recipe_has_reagent, and recipe_id on craftable items
        self.get_profession_data()
        end_of_profession_data_time = self.print_loading_time(end_of_craftable_items_time, 'profession data')

        # Get recipe details
        # Link recipe_has_trainer, recipe item id
        self.get_recipe_details()
        self.print_loading_time(end_of_profession_data_time, 'recipe details')

        self.print_loading_time(start_time, 'all data')

        # Check data
        self.check_data()

        # Close driver
        self.driver.quit()

    def check_data(self):

        # Iterate through expected row amounts
        amount_of_tables = len(self.expected_row_amount)
        amount_of_correct_tables = 0
        for table in self.expected_row_amount:
            psv_data = self.read_psv(table)
            actual_row_amount = len(psv_data[1])

            # Check if expected and actual amount are equal
            if self.expected_row_amount[table] == actual_row_amount:
                amount_of_correct_tables += 1

            # Else print the information
            else:
                print('{} has the wrong amount of rows. The expected amount was: {}, but the actual amount was: {}.\n'
                      .format(table, self.expected_row_amount[table], actual_row_amount))

        # Check if all tables have the correct row amount
        if amount_of_correct_tables == amount_of_tables:
            print('All tables have the expected amount of rows.\n')

    def get_professions(self):

        # Create professions psv file
        profession_columns = tuple([('name',
                                     'profession_link_url',
                                     'icon_link_url',
                                     'is_main_profession')])
        self.create_psv('profession', profession_columns)

        # Load professions index
        self.load_page('https://classic.wowhead.com/professions')

        # Get profession row data
        try:
            self.get_main_professions_row_data()
        except StaleElementReferenceException:

            # Reset profession psv file
            self.create_psv('profession', profession_columns)

            # Try again
            self.get_main_professions_row_data()

        # Get main profession length
        main_professions_length = len(self.read_psv('profession')[1])

        # Get expected row amount
        self.expected_row_amount['profession'] = self.get_expected_row_amount()

        # Load secondary skills index
        self.load_page('https://classic.wowhead.com/secondary-skills')

        # Get secondary profession row data
        secondary_professions = ('Cooking', 'First Aid', 'Fishing')
        try:
            self.get_secondary_professions_row_data(secondary_professions)
        except StaleElementReferenceException:

            # Reset professions to main professions
            # Get all rows except the new ones
            old_rows = self.read_psv('profession')[1][0:main_professions_length]

            # Reset file and write old lines
            self.create_psv('profession', profession_columns)
            self.write_psv_lines('profession', old_rows)

            # Try again
            self.get_secondary_professions_row_data(secondary_professions)

        # Update expected row amount
        if 'profession' not in self.expected_row_amount.keys():
            self.expected_row_amount['profession'] = 0
        self.expected_row_amount['profession'] += len(secondary_professions)

    def get_main_professions_row_data(self):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows()

            for row in rows:
                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                profession_link_url = 'https://classic.wowhead.com/' + (name.lower()).replace(' ', '-')
                icon_link_url = tds[0].find_element_by_tag_name('ins').get_attribute('style')[23:-3]
                is_main_profession = True

                # Write row to profession psv
                self.write_psv_lines('profession', tuple([(name,
                                                           profession_link_url,
                                                           icon_link_url,
                                                           is_main_profession)]))

            # Check if on last page
            if self.check_if_on_last_page():
                not_on_last_page = False

    def get_secondary_professions_row_data(self, secondary_professions):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows()

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                if name in secondary_professions:
                    profession_link_url = 'https://classic.wowhead.com/' + (name.lower()).replace(' ', '-')
                    icon_link_url = tds[0].find_element_by_tag_name('ins').get_attribute('style')[23:-3]
                    is_main_profession = False

                    # Write row to profession psv file
                    self.write_psv_lines('profession', tuple([(name,
                                                               profession_link_url,
                                                               icon_link_url,
                                                               is_main_profession)]))
                else:
                    pass

            # Check if on last page
            if self.check_if_on_last_page():
                not_on_last_page = False

    def get_locations(self):

        # Create locations psv file
        location_columns = tuple([('name',
                                   'location_link_url',
                                   'faction_status')])
        self.create_psv('location', location_columns)

        # Load locations index
        self.load_page('https://classic.wowhead.com/zones')

        # Get row data
        try:
            self.get_locations_row_data()
        except StaleElementReferenceException:

            # Reset psv_file
            self.create_psv('location', location_columns)

            # Try again
            self.get_locations_row_data()

        # Get expected row amount
        self.expected_row_amount['location'] = self.get_expected_row_amount()

    def get_locations_row_data(self):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows()

            for row in rows:
                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[0].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                location_link_url = 'https://classic.wowhead.com/' + \
                                    ((name.lower()).replace(' ', '-')).replace("'", '')
                faction_status = tds[2].find_element_by_tag_name('span').get_attribute('innerText')

                # Write row to locations file
                self.write_psv_lines('location', tuple([(name,
                                                         location_link_url,
                                                         faction_status)]))

            # Check if on last page
            if self.check_if_on_last_page():
                not_on_last_page = False

    def get_vendors(self):

        # Create vendors psv file
        vendor_columns = tuple([('name',
                                 'vendor_link_url',
                                 'reaction_to_alliance',
                                 'reaction_to_horde')])
        self.create_psv('vendor', vendor_columns)

        # Create vendor has location psv file
        vendor_has_location_columns = tuple([('vendor_id',
                                              'location_id')])
        self.create_psv('vendor_has_location', vendor_has_location_columns)

        # Get location names
        location_names = []
        locations = self.read_psv('location')[1]
        for location in locations:
            location_names.append(location[0])
        location_names = tuple(location_names)

        # Load npc index
        self.load_page('https://classic.wowhead.com/npcs')

        # Apply vendor filter
        self.apply_filters([{
            'option_group': 'Utility',
            'filter': 'Vendor',
            'evaluation_option': None,
            'option': 'Yes'
        }])

        # Get row data
        try:
            self.get_vendors_row_data(location_names)
        except StaleElementReferenceException:

            # Reset psv files
            self.create_psv('vendor', vendor_columns)
            self.create_psv('vendor_has_location', vendor_has_location_columns)

            # Try again
            self.get_vendors_row_data(location_names)

        # Get expected row amount
        self.expected_row_amount['vendor'] = self.get_expected_row_amount()

    def get_vendors_row_data(self, location_names):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows()

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                vendor_link_url = tds[1].find_element_by_tag_name('a').get_attribute('href')
                reaction_to_alliance = 'Hostile'
                reaction_to_horde = 'Hostile'
                known_alliances = tds[4].find_elements_by_tag_name('span')
                for span in known_alliances:
                    if span.get_attribute('innerText') == 'A':
                        if span.get_attribute('class') == 'q2':
                            reaction_to_alliance = 'Friendly'
                        elif span.get_attribute('class') == 'q':
                            reaction_to_alliance = 'Neutral'
                    if span.get_attribute('innerText') == 'H':
                        if span.get_attribute('class') == 'q2':
                            reaction_to_horde = 'Friendly'
                        elif span.get_attribute('class') == 'q':
                            reaction_to_horde = 'Neutral'

                # Write row to vendors file
                self.write_psv_lines('vendor', tuple([(name,
                                                       vendor_link_url,
                                                       reaction_to_alliance,
                                                       reaction_to_horde)]))

                # Get location ids
                vendors = self.read_psv('vendor')[1]
                vendor_id = vendors.index(vendors[-1]) + 1
                vendor_locations = tds[3].find_elements_by_tag_name('a')
                if len(vendor_locations) > 0:
                    for vendor_location in vendor_locations:
                        name = (vendor_location.get_attribute('innerText')).replace("'", "''")
                        location_id = location_names.index(name) + 1

                        # Link vendors to locations
                        self.write_psv_lines('vendor_has_location', tuple([(vendor_id,
                                                                            location_id)]))
                else:
                    pass

                # Increase expected row amount
                if 'vendor_has_location' not in self.expected_row_amount.keys():
                    self.expected_row_amount['vendor_has_location'] = 0
                self.expected_row_amount['vendor_has_location'] += len(vendor_locations)

            # Check if on last page
            if self.check_if_on_last_page():
                not_on_last_page = False

    def get_reagents(self):

        # Create reagents psv file
        reagent_columns = tuple([('name',
                                  'item_link_url',
                                  'icon_link_url')])
        self.create_psv('reagent', reagent_columns)

        # Create source psv file
        source_columns = tuple([tuple(['name'])])
        self.create_psv('source', source_columns)

        # Write rows to sources file
        sources = tuple([
            tuple(['Gathered']),
            tuple(['Dropped']),
            tuple(['Crafted']),
            tuple(['Bought'])])
        self.write_psv_lines('source', sources)

        # Create reagent has source psv file
        reagent_has_source = tuple([('reagent_id',
                                     'source_id')])
        self.create_psv('reagent_has_source', reagent_has_source)

        # Get location names
        location_names = []
        locations = self.read_psv('location')[1]
        for location in locations:
            location_names.append(location[0])
        location_names = tuple(location_names)

        # Get profession names
        profession_names = []
        professions = self.read_psv('profession')[1]
        for profession in professions:
            profession_names.append(profession[0])
        profession_names = tuple(profession_names)

        # Load reagents index
        self.load_page('https://classic.wowhead.com/items')

        # Apply reagent filter
        self.apply_filters([{
            'option_group': 'Professions and Economy',
            'filter': 'Reagent for ability/profession',
            'evaluation_option': None,
            'option': 'Yes'
        }])

        # Get row data
        try:
            self.get_reagents_row_data(location_names, profession_names)
        except StaleElementReferenceException:

            # Reset reagents psv file
            self.create_psv('reagent', reagent_columns)

            # Try again
            self.get_reagents_row_data(location_names, profession_names)

        # Get expected row amount
        self.expected_row_amount['reagent'] = self.get_expected_row_amount()

    def get_reagents_row_data(self, location_names, profession_names):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows()

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get reagent row data
                tds = self.get_row_data(row)
                name = (tds[2].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                item_link_url = tds[2].find_element_by_tag_name('a').get_attribute('href')
                icon_link_url = tds[1].find_element_by_tag_name('ins').get_attribute('style')[23:-3]

                # Write row to reagent file
                self.write_psv_lines('reagent', tuple([(name,
                                                        item_link_url,
                                                        icon_link_url)]))

                # Get current reagent id
                reagents = self.read_psv('reagent')[1]
                reagent_id = reagents.index(reagents[-1]) + 1

                try:
                    div_text = tds[8].find_element_by_tag_name('div').get_attribute('innerText')
                    if 'Vendor' in div_text or 'Vendors' in div_text:
                        self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 4)]))
                    else:
                        if div_text in location_names:
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 2)]))
                        if div_text in profession_names:
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 3)]))

                except NoSuchElementException:
                    try:
                        div_link = (tds[8].find_element_by_tag_name('a').get_attribute('innerText')).replace("'",
                                                                                                             "''")

                        # Check exceptions
                        if div_link == 'Evergreen Herb Casing':
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 3)]))

                        if div_link == 'Anubisath Guardian':
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 2)]))

                    except NoSuchElementException:
                        name = (tds[2].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                        sources = tds[8].get_attribute('innerText')

                        # Check if sources is empty
                        if sources == '':
                            # Check exception
                            if name == 'Refined Deeprock Salt':
                                self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 3)]))
                            else:
                                self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 1)]))

                        # Check if source is gathered
                        if 'Gathered' in sources or \
                                'Disenchanted' in sources or \
                                'Skinned' in sources or \
                                'Mined' in sources or \
                                'Fished' in sources:
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 1)]))

                        # Check if source is dropped
                        if 'Drop' in sources:
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 2)]))

                        # Check if source is crafted
                        if 'Crafted' in sources:
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 3)]))

                        # Check if source is bought
                        if 'Vendor' in sources or 'Vendors' in sources:
                            self.write_psv_lines('reagent_has_source', tuple([(reagent_id, 4)]))

            # Check if on last page
            if self.check_if_on_last_page():
                not_on_last_page = False

    def get_reagent_details(self):

        # Create reagent has vendor psv file
        reagent_has_vendor_columns = tuple([('reagent_id',
                                             'vendor_id',
                                             'buy_price')])
        self.create_psv('reagent_has_vendor', reagent_has_vendor_columns)

        # Iterate through buyable reagents
        reagent_has_vendor_length = 0
        reagents = self.read_psv('reagent')[1]
        reagent_has_source = self.read_psv('reagent_has_source')[1]
        for reagent in reagents:

            # Get reagent_id
            reagent_id = reagents.index(reagent) + 1

            print('\rGetting reagent {} of {}.'.format(reagent_id, len(reagents)), end='')

            for row in reagent_has_source:
                if reagent_id == int(row[0]) and int(row[1]) == 4:

                    # Load reagent details page
                    self.load_page(reagent[1])

                    # Check if tab exists and click it
                    if self.check_if_tab_label_exists(reagent[1], '#sold-by'):
                        bottom_advertisement_not_found = True
                        while bottom_advertisement_not_found:
                            try:
                                self.get_tab_label(reagent[1], '#sold-by').click()
                                bottom_advertisement_not_found = False
                            except ElementClickInterceptedException:
                                self.remove_bottom_advertisement()


                        # Get vendor names
                        vendor_names = []
                        vendors = self.read_psv('vendor')[1]
                        for vendor in vendors:
                            vendor_names.append(vendor[0])
                        vendor_names = tuple(vendor_names)

                        # Get row data
                        try:
                            self.get_reagent_details_sold_by_row_data(reagent_id, vendor_names)
                        except StaleElementReferenceException:

                            # Reset reagent has vendors to previous reagent
                            # Get all rows except the new ones
                            old_rows = self.read_psv('reagent_has_vendor')[1][reagent_has_vendor_length]

                            # Reset file and write old lines
                            self.create_psv('reagent_has_vendor', reagent_has_vendor_columns)
                            self.write_psv_lines('reagent_has_vendor', old_rows)

                            self.get_reagent_details_sold_by_row_data(reagent_id, vendor_names)
                        finally:
                            reagent_has_vendor_length = len(self.read_psv('reagent_has_vendor')[1])
                    else:
                        pass

            # Get expected row amount
            if 'reagent_has_vendor' not in self.expected_row_amount.keys():
                self.expected_row_amount['reagent_has_vendor'] = 0
            self.expected_row_amount['reagent_has_vendor'] += self.get_expected_row_amount()

        # Print newline
        print('\n')

    def get_reagent_details_sold_by_row_data(self, reagent_id, vendor_names):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows(tab_id='tab-sold-by')

            for row in rows:
                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                vendor_name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                vendor_id = vendor_names.index(vendor_name) + 1
                buy_price = self.get_money_amount(tds[6])

                # Write line to reagent has vendor psv file
                self.write_psv_lines('reagent_has_vendor', tuple([(reagent_id,
                                                                   vendor_id,
                                                                   buy_price)]))

            # Check if on last page
            if self.check_if_on_last_page(tab_id='tab-sold-by'):
                not_on_last_page = False

    def get_craftable_items(self):

        # Create craftable items psv file
        craftable_item_columns = tuple([('name',
                                         'item_link_url',
                                         'icon_link_url',
                                         'item_slot',
                                         'sell_price')])
        self.create_psv('craftable_item', craftable_item_columns)

        # Get craftable items for each profession
        craftable_item_length = 0
        craftable_item_names = []
        professions = self.read_psv('profession')[1]
        for profession in professions:

            # Skip Skinning because it's not in the filter list
            if profession[0] == 'Skinning':
                continue

            # Load craftable items index
            self.load_page('https://classic.wowhead.com/items')

            # Apply filters
            self.apply_filters([{
                'option_group': 'Source',
                'filter': 'Crafted by a profession',
                'evaluation_option': None,
                'option': profession[0]
            }, {
                'option_group': 'Professions and Economy',
                'filter': 'Sale price (coppers)',
                'evaluation_option': '>=',
                'option': '0'
            }])

            # Get craftable items row data
            try:
                try:
                    self.get_craftable_items_row_data(craftable_item_names)
                except NoRowsFoundException:
                    continue
            except StaleElementReferenceException:

                # Reset craftable items to previous profession
                # Get all rows except the new ones
                old_rows = self.read_psv('craftable_item')[1][0:craftable_item_length]

                # Reset file and write old lines
                self.create_psv('craftable_item', craftable_item_columns)
                self.write_psv_lines('craftable_item', old_rows)

                self.get_craftable_items_row_data(craftable_item_names)

            # Update craftable item length
            craftable_item_length = len(self.read_psv('craftable_item')[1])

            # Get expected row amount
            if 'craftable_item' not in self.expected_row_amount.keys():
                self.expected_row_amount['craftable_item'] = 0
            self.expected_row_amount['craftable_item'] += self.get_expected_row_amount()

    def get_craftable_items_row_data(self, craftable_item_names):

        not_on_last_page = True
        while not_on_last_page:

            # Check if rows exist
            self.check_if_rows_exist()

            # Get rows
            rows = self.get_rows()

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[2].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                if name not in craftable_item_names:
                    item_link_url = tds[2].find_element_by_tag_name('a').get_attribute('href')
                    icon_link_url = tds[1].find_element_by_tag_name('ins').get_attribute('style')[23:-3]

                    # Replace empty string with standard value
                    item_slot = tds[7].get_attribute('innerText')
                    if item_slot == '':
                        item_slot = 'Not equipable'

                    sell_price = tds[8].get_attribute('innerText')
                    if sell_price == '':
                        sell_price = None

                    # Write line to craftable item psv file
                    self.write_psv_lines('craftable_item', tuple([(name,
                                                                   item_link_url,
                                                                   icon_link_url,
                                                                   item_slot,
                                                                   sell_price)]))
                    craftable_item_names.append(name)

            # Check if on last page
            if self.check_if_on_last_page():
                not_on_last_page = False

    def get_profession_data(self):

        # Create trainers psv file
        trainer_columns = tuple([('name',
                                  'trainer_link_url',
                                  'reaction_to_alliance',
                                  'reaction_to_horde',
                                  'location_id')])
        self.create_psv('trainer', trainer_columns)

        # Create recipe items psv file
        recipe_item_columns = tuple([('name',
                                      'item_link_url',
                                      'icon_link_url',
                                      'required_skill_level',
                                      'profession_id')])
        self.create_psv('recipe_item', recipe_item_columns)

        # Create recipes psv file
        recipe_columns = tuple([('name',
                                 'difficulty_requirement',
                                 'difficulty_category_1',
                                 'difficulty_category_2',
                                 'difficulty_category_3',
                                 'difficulty_category_4',
                                 'recipe_link_url',
                                 'icon_link_url',
                                 'minimum_amount_created',
                                 'maximum_amount_created',
                                 'training_cost',
                                 'recipe_item_id',
                                 'craftable_item_id',
                                 'profession_id')])
        self.create_psv('recipe', recipe_columns)

        # Create recipe has reagent psv file
        recipe_has_reagent_columns = tuple([('recipe_id',
                                             'reagent_id',
                                             'amount')])
        self.create_psv('recipe_has_reagent', recipe_has_reagent_columns)

        # Create trainer_has_profession
        trainer_has_profession = tuple([('trainer_id',
                                         'profession_id')])
        self.create_psv('trainer_has_profession', trainer_has_profession)

        # Get location names
        location_names = []
        locations = self.read_psv('location')[1]
        for location in locations:
            location_names.append(location[0])
        location_names = tuple(location_names)

        # Get craftable link urls
        craftable_item_link_urls = []
        craftable_items = self.read_psv('craftable_item')[1]
        for item in craftable_items:
            craftable_item_link_urls.append(item[1])
        craftable_item_link_urls = tuple(craftable_item_link_urls)

        # Get reagent link urls
        reagent_link_urls = []
        reagents = self.read_psv('reagent')[1]
        for reagent in reagents:
            reagent_link_urls.append(reagent[1])
        reagent_link_urls = tuple(reagent_link_urls)

        # Get vendor names
        vendor_names = []
        vendors = self.read_psv('vendor')[1]
        for vendor in vendors:
            vendor_names.append(vendor[0])
        vendor_names = tuple(vendor_names)

        # Get all remaining data for each profession
        trainer_length = 0
        recipe_item_length = 0
        recipe_length = 0
        recipe_has_reagent_length = 0
        trainers = []
        professions = self.read_psv('profession')[1]
        for profession in professions:

            # Load target profession page
            self.load_page(profession[1])

            # Get profession id
            profession_id = professions.index(profession) + 1

            # Check if trainers tab exists and click it
            if self.check_if_tab_label_exists(profession[1], '#trainers'):
                bottom_advertisement_not_found = True
                while bottom_advertisement_not_found:
                    try:
                        self.get_tab_label(profession[1], '#trainers').click()
                        bottom_advertisement_not_found = False
                    except ElementClickInterceptedException:
                        self.remove_bottom_advertisement()

                # Get trainers row data
                try:
                    self.get_trainers_row_data(profession_id, location_names, trainers)
                except StaleElementReferenceException:

                    # Reset trainers to previous profession
                    # Get all rows except the new ones
                    old_rows = self.read_psv('trainer')[1][0:trainer_length]

                    # Reset trainers file and write old rows
                    self.create_psv('trainer', trainer_columns)
                    self.write_psv_lines('trainer', old_rows)

                    # Try again
                    self.get_trainers_row_data(profession_id, location_names, trainers)

                # Update trainer length
                trainer_length = len(self.read_psv('trainer')[1])

                # Get expected row amount
                if 'trainer' not in self.expected_row_amount.keys():
                    self.expected_row_amount['trainer'] = 0
                self.expected_row_amount['trainer'] += self.get_expected_row_amount(tab_id='tab-trainers')

            # Check if recipe items tab exists and click it
            if self.check_if_tab_label_exists(profession[1], '#recipe-items'):
                bottom_advertisement_not_found = True
                while bottom_advertisement_not_found:
                    try:
                        self.get_tab_label(profession[1], '#recipe-items').click()
                        bottom_advertisement_not_found = False
                    except ElementClickInterceptedException:
                        self.remove_bottom_advertisement()

                # Get recipe items row data
                try:
                    self.get_recipe_items_row_data(profession_id)
                except StaleElementReferenceException:

                    # Reset recipe items to previous profession
                    # Get all rows except the new ones
                    old_rows = self.read_psv('recipe_item')[1][0:recipe_item_length]

                    # Reset recipe items file and write old rows
                    self.create_psv('recipe_item', recipe_item_columns)
                    self.write_psv_lines('recipe_item', old_rows)

                    self.get_recipe_items_row_data(profession_id)

                # Update recipe item length
                recipe_item_length = len(self.read_psv('recipe_item')[1])

                # Get expected row amount
                if 'recipe_item' not in self.expected_row_amount.keys():
                    self.expected_row_amount['recipe_item'] = 0
                self.expected_row_amount['recipe_item'] += self.get_expected_row_amount(tab_id='tab-recipe-items')

            # Check if recipes tab exists and click it
            if self.check_if_tab_label_exists(profession[1], '#recipes'):
                bottom_advertisement_not_found = True
                while bottom_advertisement_not_found:
                    try:
                        self.get_tab_label(profession[1], '#recipes').click()
                        bottom_advertisement_not_found = False
                    except ElementClickInterceptedException:
                        self.remove_bottom_advertisement()

                # Get recipes row data
                try:
                    self.get_recipes_row_data(profession_id,
                                              reagent_link_urls,
                                              craftable_item_link_urls,
                                              vendor_names)
                except StaleElementReferenceException:

                    # Reset recipes to previous profession
                    # Get all rows except the new ones
                    old_rows = self.read_psv('recipe')[1][0:recipe_length]

                    # Reset recipe file and write old rows
                    self.create_psv('recipe', recipe_columns)
                    self.write_psv_lines('recipe', old_rows)

                    # Try again
                    self.get_recipes_row_data(profession_id,
                                              reagent_link_urls,
                                              craftable_item_link_urls,
                                              vendor_names)

                # Update lengths
                recipe_length = len(self.read_psv('recipe')[1])
                recipe_has_reagent_length = len(self.read_psv('recipe_has_reagent')[1])

                # Get expected row amount
                if 'recipe' not in self.expected_row_amount.keys():
                    self.expected_row_amount['recipe'] = 0
                self.expected_row_amount['recipe'] += self.get_expected_row_amount(tab_id='tab-recipes')

    def get_trainers_row_data(self, profession_id, location_names, trainers):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows(tab_id='tab-trainers')

            for row in rows:
                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                trainer_id = None
                for trainer in trainers:
                    if trainer[1] == name:
                        trainer_id = trainer[0]

                if trainer_id is None:
                    trainer_link_url = tds[1].find_element_by_tag_name('a').get_attribute('href')
                    reaction_to_alliance = 'Hostile'
                    reaction_to_horde = 'Hostile'
                    known_alliances = tds[4].find_elements_by_tag_name('span')
                    for span in known_alliances:
                        if span.get_attribute('innerText') == 'A':
                            if span.get_attribute('class') == 'q2':
                                reaction_to_alliance = 'Friendly'
                            elif span.get_attribute('class') == 'q':
                                reaction_to_alliance = 'Neutral'
                        if span.get_attribute('innerText') == 'H':
                            if span.get_attribute('class') == 'q2':
                                reaction_to_horde = 'Friendly'
                            elif span.get_attribute('class') == 'q':
                                reaction_to_horde = 'Neutral'

                    location_id = None
                    try:
                        location_name = tds[3].find_element_by_tag_name('a').get_attribute('innerText')
                        if location_name in location_names:
                            location_id = location_names.index(location_name) + 1
                    except NoSuchElementException:
                        pass

                    # Write row to trainer psv file
                    self.write_psv_lines('trainer', tuple([(name,
                                                            trainer_link_url,
                                                            reaction_to_alliance,
                                                            reaction_to_horde,
                                                            location_id)]))

                    if len(trainers) > 0:
                        trainers.sort(key=lambda tup: tup[0])
                        trainer_id = trainers[-1][0] + 1
                    else:
                        trainer_id = 1
                    trainers.append(tuple([trainer_id, name]))

                # Write row to trainer has profession
                self.write_psv_lines('trainer_has_profession', tuple([(trainer_id,
                                                                       profession_id)]))

            # Check if on last page
            if self.check_if_on_last_page(tab_id='tab-trainers'):
                not_on_last_page = False

    def get_recipe_items_row_data(self, profession_id):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows(tab_id='tab-recipe-items')

            for row in rows:
                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[2].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                item_link_url = tds[2].find_element_by_tag_name('a').get_attribute('href')
                icon_link_url = tds[1].find_element_by_tag_name('ins').get_attribute('style')[23:-3]
                required_skill_level = tds[7].get_attribute('innerText')

                # Write row to recipe items psv file
                self.write_psv_lines('recipe_item', tuple([(name,
                                                            item_link_url,
                                                            icon_link_url,
                                                            required_skill_level,
                                                            profession_id)]))

            # Check if on last page
            if self.check_if_on_last_page(tab_id='tab-recipe-items'):
                not_on_last_page = False

    def get_recipes_row_data(self,
                             profession_id,
                             reagent_link_urls,
                             craftable_item_link_urls,
                             vendor_names):

        not_on_last_page = True
        while not_on_last_page:

            # Initialise not existent reagent list
            not_existent_reagents = []

            # Get rows
            rows = self.get_rows(tab_id='tab-recipes')

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = ((tds[2].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")) \
                    .replace('"', "''")

                try:
                    difficulty_requirement = tds[5].find_elements_by_tag_name('div')[0].find_element_by_tag_name(
                        'span').get_attribute('innerText')
                except (IndexError, NoSuchElementException):
                    difficulty_requirement = 1

                try:
                    difficulty_category_1 = tds[5].find_elements_by_tag_name('div')[1].find_element_by_class_name(
                        'r1').get_attribute('innerText')
                except (IndexError, NoSuchElementException):
                    difficulty_category_1 = difficulty_requirement

                try:
                    difficulty_category_2 = tds[5].find_elements_by_tag_name('div')[1].find_element_by_class_name(
                        'r2').get_attribute('innerText')
                except (IndexError, NoSuchElementException):
                    difficulty_category_2 = difficulty_category_1

                try:
                    difficulty_category_3 = tds[5].find_elements_by_tag_name('div')[1].find_element_by_class_name(
                        'r3').get_attribute('innerText')
                except (IndexError, NoSuchElementException):
                    difficulty_category_3 = difficulty_category_2

                try:
                    difficulty_category_4 = tds[5].find_elements_by_tag_name('div')[1].find_element_by_class_name(
                        'r4').get_attribute('innerText')
                except (IndexError, NoSuchElementException):
                    difficulty_category_4 = difficulty_category_3

                recipe_link_url = tds[2].find_element_by_tag_name('a').get_attribute('href')
                icon_link_url = tds[1].find_element_by_tag_name('ins').get_attribute('style')[23:-3]
                minimum_amount_created = maximum_amount_created = 1

                try:
                    amounts_created = (tds[1]
                                       .find_element_by_tag_name('span')
                                       .find_elements_by_tag_name('div')[0]
                                       .get_attribute('innerText')) \
                        .split('-')
                    if len(amounts_created) == 2:
                        minimum_amount_created = amounts_created[0]
                        maximum_amount_created = amounts_created[1]
                    elif len(amounts_created) == 1:
                        minimum_amount_created = maximum_amount_created = amounts_created[0]

                except NoSuchElementException:
                    pass

                training_cost = None

                craftable_item_id = None
                craftable_item_url = tds[1] \
                    .find_element_by_class_name('iconmedium') \
                    .find_element_by_tag_name('a') \
                    .get_attribute('href')
                if craftable_item_url in craftable_item_link_urls:
                    craftable_item_id = craftable_item_link_urls.index(craftable_item_url) + 1

                recipe_item_id = None

                # Write row to recipe psv file
                self.write_psv_lines('recipe', tuple([(name,
                                                       difficulty_requirement,
                                                       difficulty_category_1,
                                                       difficulty_category_2,
                                                       difficulty_category_3,
                                                       difficulty_category_4,
                                                       recipe_link_url,
                                                       icon_link_url,
                                                       minimum_amount_created,
                                                       maximum_amount_created,
                                                       training_cost,
                                                       recipe_item_id,
                                                       craftable_item_id,
                                                       profession_id)]))

                # Link recipe to reagents
                recipe_reagents = tds[3].find_elements_by_class_name('iconmedium')
                recipes = self.read_psv('recipe')[1]
                last_recipe = self.read_psv('recipe')[1][-1]
                recipe_id = recipes.index(last_recipe) + 1
                for recipe_reagent in recipe_reagents:
                    try:
                        amount = recipe_reagent \
                            .find_element_by_tag_name('span') \
                            .find_elements_by_tag_name('div')[0] \
                            .get_attribute('innerText')
                    except NoSuchElementException:
                        amount = 1
                    reagent_link_url = recipe_reagent.find_element_by_tag_name('a').get_attribute('href')
                    if reagent_link_url in reagent_link_urls:
                        reagent_id = reagent_link_urls.index(reagent_link_url) + 1
                    else:

                        # Check if there already are not existent reagents
                        reagent_id = len(reagent_link_urls) + 1
                        reagent_icon_link = recipe_reagent.find_element_by_tag_name('ins').get_attribute('style')[23:-3]
                        if len(not_existent_reagents) > 0:
                            # Check if reagent link url matches with one
                            for reagent in not_existent_reagents:
                                if reagent_link_url == reagent[1]:
                                    reagent_id = reagent[0]
                                else:
                                    not_existent_reagents.sort(key=lambda tup: tup[0])
                                    reagent_id = not_existent_reagents[-1][0] + 1
                                    not_existent_reagents.append(tuple([reagent_id, reagent_link_url, reagent_icon_link]))
                        else:
                            not_existent_reagents.append(tuple([reagent_id, reagent_link_url, reagent_icon_link]))

                    # Write row to recipe has reagent psv file
                    self.write_psv_lines('recipe_has_reagent', tuple([(recipe_id,
                                                                       reagent_id,
                                                                       amount)]))

                # Get expected row amount
                if 'recipe_has_reagent' not in self.expected_row_amount.keys():
                    self.expected_row_amount['recipe_has_reagent'] = 0
                self.expected_row_amount['recipe_has_reagent'] += len(recipe_reagents)

            # Add not existent reagents
            current_page_url = self.driver.current_url
            not_existent_reagents.sort(key=lambda tup: tup[0])
            for reagent in not_existent_reagents:
                self.get_missing_reagent_details(reagent[0], reagent[1], reagent[2], vendor_names)

            # Update reagent_link_urls
            reagent_link_urls = []
            reagents = self.read_psv('reagent')[1]
            for reagent in reagents:
                reagent_link_urls.append(reagent[1])
            reagent_link_urls = tuple(reagent_link_urls)

            # Return to index page
            self.load_page(current_page_url)

            # Check if on last page
            if self.check_if_on_last_page(tab_id='tab-recipes'):
                not_on_last_page = False

    def get_missing_reagent_details(self, reagent_id, item_link_url, icon_link_url, vendor_names):

        # load reagent page
        self.load_page(item_link_url)

        # Get reagent name and write to reagent psv file
        reagent_name = self.driver.find_elements_by_class_name('heading-size-1')[0] \
            .get_attribute('innerText')
        self.write_psv_lines('reagent', tuple([(reagent_name,
                                                item_link_url,
                                                icon_link_url)]))

        # Check if reagent is gathered
        # Gathered, Disenchanting, Skinning, Fished
        if self.check_if_tab_label_exists(item_link_url, '#gathered-from-object') or \
                self.check_if_tab_label_exists(item_link_url, '#disenchanted-from') or \
                self.check_if_tab_label_exists(item_link_url, '#skinned-from') or \
                self.check_if_tab_label_exists(item_link_url, '#mined-from-object') or \
                self.check_if_tab_label_exists(item_link_url, '#fished-in'):
            self.write_psv_lines('reagent_has_source', tuple([(reagent_id,
                                                               1)]))

        # Check if reagent is dropped
        if self.check_if_tab_label_exists(item_link_url, '#dropped_by'):
            self.write_psv_lines('reagent_has_source', tuple([(reagent_id,
                                                               2)]))

        # Check if reagent is crafted
        if self.check_if_tab_label_exists(item_link_url, '#created-by-spell'):
            self.write_psv_lines('reagent_has_source', tuple([(reagent_id,
                                                               3)]))

        # Check if new reagent is sold by vendors
        if self.check_if_tab_label_exists(item_link_url, '#sold-by'):
            self.get_tab_label(item_link_url, '#sold-by').click()
            vendor_rows = self.get_rows(tab_id='tab-sold-by')
            for row in vendor_rows:
                tds = self.get_row_data(row)
                vendor_name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                vendor_id = vendor_names.index(vendor_name) + 1
                buy_price = self.get_money_amount(tds[6])
                self.write_psv_lines('reagent_has_vendor', tuple([(reagent_id,
                                                                   vendor_id,
                                                                   buy_price)]))
            self.write_psv_lines('reagent_has_source', tuple([(reagent_id,
                                                               4)]))

    def get_recipe_details(self):

        # Set standard values
        current_recipe_index = 0
        recipe_has_trainer_length = 0
        recipe_details_columns = tuple([('recipe_id',
                                         'training_cost',
                                         'recipe_item_id',
                                         'recipe_has_trainer_length')])
        recipe_has_trainer_columns = tuple([('recipe_id',
                                             'trainer_id',)])

        # Check if temporary recipe details file exists
        temporary_recipe_details_exists = os.path.isfile('data/temporary_recipe_details.psv')
        if temporary_recipe_details_exists:

            # Get current recipe id
            recipe_details = self.read_psv('temporary_recipe_details')[1]
            current_recipe_index = len(recipe_details)
            recipe_has_trainer_length = int(recipe_details[-1][3])

            # Reset recipe has trainers to previous recipe
            # Get all rows except the new ones
            old_rows = self.read_psv('recipe_has_trainer')[1][0:recipe_has_trainer_length]

            # Reset file and write old lines
            self.create_psv('recipe_has_trainer', recipe_has_trainer_columns)
            self.write_psv_lines('recipe_has_trainer', old_rows)

        else:

            # Create temporary recipe details psv file
            self.create_psv('temporary_recipe_details', recipe_details_columns)

            # Create recipe has trainer psv file
            self.create_psv('recipe_has_trainer', recipe_has_trainer_columns)

        # Get details for each recipe
        recipes = self.read_psv('recipe')[1]

        for recipe in recipes[current_recipe_index:]:

            # Get recipe id
            recipe_id = recipes.index(recipe) + 1

            try:
                recipe_has_trainer_length = int(self.get_single_recipe_details(recipe,
                                                                               recipe_id,
                                                                               recipe_has_trainer_length,
                                                                               recipe_has_trainer_columns))
            except ServerErrorException:
                missing_details = open('data/missing_recipe_details.txt', 'a')
                missing_details.write(str(recipes.index(recipe) + 1) + '\n')
                missing_details.close()
                continue

            # Print progress
            print('\rGot recipe {} of {}.'
                  .format(recipe_id, len(recipes)),
                  end='')

        # Print newline
        print('\n')

        # Create new values dictionary
        training_costs = []
        recipe_item_id_list = []
        for recipe in self.read_psv('temporary_recipe_details')[1]:
            training_costs.append(tuple([recipe[0], recipe[1]]))
            recipe_item_id_list.append(tuple([recipe[0], recipe[2]]))
        training_costs = tuple(training_costs)
        recipe_item_id_list = tuple(recipe_item_id_list)
        new_values = {
            '10': training_costs,
            '11': recipe_item_id_list
        }

        # Replace old with new values
        self.replace_values_in_psv_file('recipe', new_values)

        # Remove temporary file
        os.remove('data/temporary_recipe_details.psv')

    def get_single_recipe_details(self,
                                  recipe,
                                  recipe_id,
                                  recipe_has_trainer_length,
                                  recipe_has_trainer_columns):

        # Load recipe details page
        self.load_page(recipe[6])

        # Get recipe item names
        recipe_item_names = []
        recipe_items = self.read_psv('recipe_item')[1]
        for recipe_item in recipe_items:
            recipe_item_names.append(recipe_item[0])
        recipe_item_names = tuple(recipe_item_names)

        # Get trainer names
        trainer_names = []
        trainers = self.read_psv('trainer')[1]
        for trainer in trainers:
            trainer_names.append(trainer[0])
        trainer_names = tuple(trainer_names)

        # Check if training cost exists and get it
        training_cost = None
        try:
            infobox_content = self.driver.find_element_by_id('infobox-contents-0')
        except NoSuchElementException:
            raise ServerErrorException('Server error on recipe {}\n'.format(recipe_id + 1))
        list_items = infobox_content.find_elements_by_tag_name('li')
        for list_item in list_items:
            if list_item.get_attribute('innerText')[0:14] == 'Training cost:':
                training_cost = self.get_money_amount(list_item)

        # Check if taught by item tab exists and click it
        recipe_item_id = None
        if self.check_if_tab_label_exists(recipe[6], '#taught-by-item'):
            bottom_advertisement_not_found = True
            while bottom_advertisement_not_found:
                try:
                    self.get_tab_label(recipe[6], '#taught-by-item').click()
                    bottom_advertisement_not_found = False
                except ElementClickInterceptedException:
                    self.remove_bottom_advertisement()

            # Get taught by item row data
            try:
                recipe_item_id = self.get_recipe_details_taught_by_item_row_data(recipe_item_names)
            except StaleElementReferenceException:
                # Try again
                recipe_item_id = self.get_recipe_details_taught_by_item_row_data(recipe_item_names)

        # Check if taught by npc tab exists and click it
        if self.check_if_tab_label_exists(recipe[6], '#taught-by-npc'):
            bottom_advertisement_not_found = True
            while bottom_advertisement_not_found:
                try:
                    self.get_tab_label(recipe[6], '#taught-by-npc').click()
                    bottom_advertisement_not_found = False
                except ElementClickInterceptedException:
                    self.remove_bottom_advertisement()

            # Get taught by npc row data
            try:
                recipe_has_trainer_length += int(
                    self.get_recipe_details_taught_by_npc_row_data(recipe_id, trainer_names))
            except StaleElementReferenceException:

                # Reset recipe has trainers to previous recipe
                # Get all rows except the new ones
                old_rows = self.read_psv('recipe_has_trainer')[1][0:recipe_has_trainer_length]

                # Reset file and write old lines
                self.create_psv('recipe_has_trainer', recipe_has_trainer_columns)
                self.write_psv_lines('recipe_has_trainer', old_rows)

                # Try again
                recipe_has_trainer_length += int(
                    self.get_recipe_details_taught_by_npc_row_data(recipe_id, trainer_names))

                # Get expected row amount
                if 'recipe_has_trainer' not in self.expected_row_amount.keys():
                    self.expected_row_amount['recipe_has_trainer'] = 0
                self.expected_row_amount['recipe_has_trainer'] += self.get_expected_row_amount(
                    tab_id='tab-taught-by-npc')

        # Add row to temporary recipe details file
        self.write_psv_lines('temporary_recipe_details', tuple([(recipe_id,
                                                                 training_cost,
                                                                 recipe_item_id,
                                                                 recipe_has_trainer_length)]))

        return recipe_has_trainer_length

    def get_recipe_details_taught_by_item_row_data(self, recipe_item_names):

        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows(tab_id='tab-taught-by-item')

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[2].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                if name in recipe_item_names:
                    item_id = recipe_item_names.index(name) + 1
                    return item_id

            # Check if on last page
            if self.check_if_on_last_page(tab_id='tab-taught-by-item'):
                not_on_last_page = False

    def get_recipe_details_taught_by_npc_row_data(self, recipe_id, trainer_names):

        trainer_amount = 0
        not_on_last_page = True
        while not_on_last_page:

            # Get rows
            rows = self.get_rows(tab_id='tab-taught-by-npc')

            for row in rows:

                # Remove video if it still exists
                self.remove_video()

                # Get row data
                tds = self.get_row_data(row)
                name = (tds[1].find_element_by_tag_name('a').get_attribute('innerText')).replace("'", "''")
                if name in trainer_names:
                    trainer_id = trainer_names.index(name) + 1
                    trainer_amount += 1

                    # Write row to recipe has trainer psv file
                    self.write_psv_lines('recipe_has_trainer', tuple([(recipe_id,
                                                                       trainer_id)]))

            # Check if on last page
            if self.check_if_on_last_page(tab_id='tab-taught-by-npc'):
                not_on_last_page = False

        return trainer_amount

    def check_if_rows_exist(self):
        main_contents = self.driver.find_element_by_id('main-contents')
        divs = main_contents.find_elements_by_class_name('text')
        for div in divs:
            if div.get_attribute('innerText') == 'Your criteria did not match any items.':
                raise NoRowsFoundException('No rows were found.')

    def get_rows(self, tab_id=None):

        table_not_found = True
        while table_not_found:
            try:
                listview = self.driver.find_element_by_class_name('listview')
                if tab_id is not None:
                    target_tab = listview.find_element_by_id(tab_id)
                    scroller = target_tab.find_element_by_class_name('listview-scroller')
                else:
                    scroller = listview.find_element_by_class_name('listview-scroller')
                table = scroller.find_element_by_class_name('listview-mode-default')
                table_not_found = False
                return table.find_element_by_tag_name('tbody').find_elements_by_tag_name('tr')
            except NoSuchElementException:
                pass

    def get_row_data(self, row):

        return row.find_elements_by_tag_name('td')

    def get_expected_row_amount(self, tab_id=None):

        not_found_expected_amount = True
        while not_found_expected_amount:
            try:
                list_view = self.driver.find_element_by_class_name('listview')
                if tab_id is not None:
                    target_tab = list_view.find_element_by_id(tab_id)
                    list_view_nav = target_tab.find_element_by_class_name('listview-nav')
                else:
                    list_view_nav = list_view.find_element_by_class_name('listview-nav')

                return int(list_view_nav
                           .find_element_by_tag_name('span')
                           .find_elements_by_tag_name('b')[2]
                           .get_attribute('innerText'))
            except (NoSuchElementException, StaleElementReferenceException):
                pass

    def get_money_amount(self, money_container):

        # Define currency variables
        gold = '0'
        silver = '00'
        copper = '00'

        # Check if gold exists
        try:
            gold = money_container.find_element_by_class_name('moneygold').get_attribute('innerText')
        except NoSuchElementException:
            pass

        # Check if silver exists
        try:
            silver = money_container.find_element_by_class_name('moneysilver').get_attribute('innerText')
        except NoSuchElementException:
            pass

        # Check if copper exists
        try:
            copper = money_container.find_element_by_class_name('moneycopper').get_attribute('innerText')
        except NoSuchElementException:
            pass

        # Return total
        return int(gold + silver + copper)

    def load_page(self, url):

        if self.driver is not None:
            # Close old driver
            self.driver.quit()

        # Start new driver
        self.driver = self.start_driver()

        # Maximise page
        self.driver.maximize_window()

        # Move cursor to the top left
        self.move_cursor_to_top_left()

        # Get page
        self.driver.get(url)

        # Restart page if cookie notice can't be removed
        try:
            self.remove_cookie_notice()
        except ElementNotInteractableException:
            self.load_page(url)

        # Try to remove bottom google advertisement
        self.remove_bottom_advertisement()

        # Try to remove the video popup if possible
        self.remove_video()

        # Move cursor to the left top
        ActionChains(self.driver) \
            .move_to_element(self.driver.find_element_by_tag_name('body')) \
            .perform()

    def remove_cookie_notice(self):

        try:
            self.driver.find_element_by_id('initial-deactivate').click()
            self.driver.find_element_by_id('as-oil-optout-confirm').find_elements_by_tag_name('button')[1].click()
        except NoSuchElementException:
            pass

    def remove_bottom_advertisement(self):

        bottom_footers = self.driver.find_elements_by_class_name('mobile-footer-closer')
        if len(bottom_footers) > 0:
            for footer in bottom_footers:
                footer.click()

    def remove_video(self):
        try:
            self.driver.find_element_by_id('ac-container').find_element_by_class_name('ac-closer').click()
        except (NoSuchElementException, ElementNotInteractableException):
            pass

    def check_if_on_last_page(self, tab_id=None):

        list_view = self.driver.find_element_by_class_name('listview')
        if tab_id is not None:
            target_tab = list_view.find_element_by_id(tab_id)
            list_view_nav = target_tab.find_element_by_class_name('listview-nav')
        else:
            list_view_nav = list_view.find_element_by_class_name('listview-nav')
        nav_elements = list_view_nav.find_elements_by_tag_name('a')
        next_element = ''
        for element in nav_elements:
            if element.get_attribute('innerText') == 'Next ›':
                next_element = element

        if next_element.get_attribute('data-visible') == 'yes':
            self.click_on_next(next_element)
            return False
        elif next_element.get_attribute('data-visible') == 'no':
            return True

    def click_on_next(self, next_element):

        self.move_cursor_to_top_left()
        self.driver.execute_script('window.scrollTo(0,0)')
        next_element.click()

    def check_if_tab_label_exists(self, current_url, tab_label_anchor):

        tab_label_exists = False
        tab_labels_are_not_found = True
        while tab_labels_are_not_found:
            try:
                tab_labels_container = self.driver.find_element_by_id('jkbfksdbl4')
                tab_labels = tab_labels_container.find_element_by_tag_name('ul').find_elements_by_tag_name('li')
                for label in tab_labels:
                    if label.find_element_by_tag_name('a').get_attribute('href') == current_url + tab_label_anchor:
                        tab_label_exists = True
                tab_labels_are_not_found = False
            except (NoSuchElementException, StaleElementReferenceException):
                pass
        return tab_label_exists

    def get_tab_label(self, current_url, tab_label_anchor):
        target_tab_label = ''
        tab_labels_container = self.driver.find_element_by_id('jkbfksdbl4')
        tab_labels = tab_labels_container.find_element_by_tag_name('ul').find_elements_by_tag_name('li')
        for tab in tab_labels:
            if tab.find_element_by_tag_name('a').get_attribute('href') == current_url + tab_label_anchor:
                target_tab_label = tab
        return target_tab_label

    def apply_filters(self, filters):

        # Get filter container
        filter_container = self.driver.find_element_by_id('filter-filters')

        # Apply filters
        for filter in filters:
            # Click on filter select
            current_filter = filter_container.find_elements_by_tag_name('div')[filters.index(filter)]
            filter_select = current_filter.find_element_by_tag_name('select')
            filter_select.click()

            # Click on filter option
            current_optgroup = ''
            optgroups = filter_select.find_elements_by_tag_name('optgroup')
            for optgroup in optgroups:
                if optgroup.get_attribute('label') == filter['option_group']:
                    current_optgroup = optgroup
            filter_options = current_optgroup.find_elements_by_tag_name('option')
            for filter_option in filter_options:
                if filter_option.get_attribute('innerText') == filter['filter']:
                    filter_option.click()

            # Check if evaluation is not None
            if filter['evaluation_option'] is not None:

                # Click on evaluation option
                evaluation_select = current_filter.find_elements_by_tag_name('select')[1]
                evaluation_select.click()
                evaluations = evaluation_select.find_elements_by_tag_name('option')
                for evaluation in evaluations:
                    if evaluation.get_attribute('innerText') == filter['evaluation_option']:
                        evaluation.click()

                        # Click on option value and enter amount
                        option_input = current_filter.find_element_by_tag_name('input')
                        option_input.click()
                        if not filter['option'] == '0':

                            # Remove standard 0
                            option_input.send_keys(Keys.ARROW_RIGHT)
                            option_input.send_keys(Keys.BACK_SPACE)

                            # Type amount
                            for character in filter['option']:
                                option_input.send_keys(character)
            else:
                # Click on option value
                option_select = current_filter.find_elements_by_tag_name('select')[1]
                option_select.click()
                options = option_select.find_elements_by_tag_name('option')
                for option in options:
                    if option.get_attribute('innerText') == filter['option']:
                        option.click()

        # Click on apply filter button
        # Find filter form
        filter_form = self.driver.find_element_by_id('fi').find_element_by_tag_name('form')
        # Find button div
        button_div = ''
        divs = filter_form.find_elements_by_tag_name('div')
        for div in divs:
            if div.get_attribute('class') == 'filter-row':
                button_div = div
        # Find apply filter button
        apply_filter_button = ''
        buttons = button_div.find_elements_by_tag_name('button')
        for button in buttons:
            if button.get_attribute('innerText') == 'Apply filter':
                apply_filter_button = button
        apply_filter_button.click()

    def create_psv(self, filename, columns):

        # Check if data directory exists
        if not os.path.isdir('../data'):
            os.makedirs('../data')

        # Open file
        psv_file = open('data/' + filename + '.psv', 'w')

        # Close file so it's created
        psv_file.close()

        # Write columns
        self.write_psv_lines(filename, columns)

    def write_psv_lines(self, filename, rows):

        # Open file
        psv_file = open('data/' + filename + '.psv', 'a')

        for row_data in rows:

            # Format row data into psv format
            psv_line = ''
            current_index = 0
            last_index = len(row_data) - 1
            for value in row_data:
                if current_index == last_index:
                    psv_line += str(value) + '\n'
                else:
                    psv_line += str(value) + '|'
                current_index += 1

            # Write line
            psv_file.write(psv_line)

        # Close file
        psv_file.close()

    def read_psv(self, filename):

        # Open file in read mode
        psv_file = open('data/' + filename + '.psv', 'r')

        # Iterate through psv lines
        columns = []
        rows = []
        lines = psv_file.readlines()
        for line in lines:

            # Remove newline
            no_newline = line.replace('\n', '')

            # Split line into values
            values = no_newline.split('|')

            # First line is columns
            if line == lines[0]:
                for value in values:
                    columns.append(value)
                columns = tuple([tuple(columns)])

            # Other lines are rows
            else:
                row = []
                for value in values:
                    row.append(value)
                row = tuple(row)
                rows.append(row)
        rows = tuple(rows)

        # Close file
        psv_file.close()

        psv_tuple = (columns, rows)
        return psv_tuple

    def replace_values_in_psv_file(self, filename, new_values_dictionary):

        # Get old values
        columns = self.read_psv(filename)[0]
        old_rows = self.read_psv(filename)[1]

        # Replace standard with new values
        current_row = 1
        new_rows = []
        for row in old_rows:
            current_value = 0
            new_row = []
            for value in row:
                if str(current_value) in new_values_dictionary.keys():
                    new_value_found = False
                    new_values = new_values_dictionary[str(current_value)]
                    for new_value in new_values:
                        if str(new_value[0]) == str(current_row):
                            new_row.append(str(new_value[1]))
                            new_value_found = True
                            break
                    if not new_value_found:
                        new_row.append(str(value))
                else:
                    new_row.append(str(value))
                current_value += 1
            new_row = tuple(new_row)
            new_rows.append(new_row)
            current_row += 1
        new_rows = tuple(new_rows)

        # Write new rows
        self.create_psv(filename, columns)
        self.write_psv_lines(filename, new_rows)

    def print_loading_time(self, start_time, data_name):

        end_time = time.time()
        difference = round(end_time - start_time)
        print('It took: {} to load the {}.\n'.format(str(datetime.timedelta(seconds=difference)), data_name))
        return end_time

    def move_cursor_to_top_left(self):
        ActionChains(self.driver) \
            .move_to_element(self.driver.find_element_by_tag_name('body')) \
            .perform()