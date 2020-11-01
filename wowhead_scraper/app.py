import datetime
import time

from flask import Flask, make_response
from wowhead_scraper.scraper import WowheadScraper
from wowhead_scraper.sql_connector import SQLConnector
from wowhead_scraper.time import TimeHelper

app = Flask(__name__)

frontendDomain = 'https://wow-classic-assistant.test'


@app.route('/')
def home():
    return 'Hello'


@app.route('/clear-data')
def clear_data():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time_helper = TimeHelper()

    # Perform scraper function
    try:
        wowhead_scraper = WowheadScraper()
        clear_data_result = wowhead_scraper.clear_data()
        scraper_delta = time_helper.get_timedelta_from_time_periods(clear_data_result['start_time'],
                                                                    clear_data_result['end_time'])
        scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    except ():
        scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Content-Type'] = 'application/json'
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/professions')
def get_professions():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     professions_result = wowhead_scraper.get_professions()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(professions_result['start_time'], professions_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Content-Type'] = 'application/json'
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/locations')
def get_locations():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     locations_result = wowhead_scraper.get_locations()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(locations_result['start_time'], locations_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/vendors')
def get_vendors():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     vendors_result = wowhead_scraper.get_vendors()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(vendors_result['start_time'], vendors_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/reagents')
def get_reagents():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     reagents_result = wowhead_scraper.get_reagents()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(reagents_result['start_time'], reagents_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/reagent-details')
def get_reagent_details():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     reagent_details_result = wowhead_scraper.get_reagent_details()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(reagent_details_result['start_time'], reagent_details_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/craftable-items')
def get_craftable_items():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     craftable_items_result = wowhead_scraper.get_craftable_items()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(craftable_items_result['start_time'], craftable_items_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/profession-data')
def get_profession_data():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     profession_data_result = wowhead_scraper.get_profession_data()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(profession_data_result['start_time'], profession_data_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/recipe-details')
def get_recipe_details():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     recipe_details_result = wowhead_scraper.get_recipe_details()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(recipe_details_result['start_time'], recipe_details_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/check-data')
def check_data():

    # Start timer
    start_response_time = time.time()

    # Initialise helpers
    time.sleep(2)
    time_helper = TimeHelper()

    # Perform scraper function
    # try:
    #     wowhead_scraper = WowheadScraper()
    #     check_data_result = wowhead_scraper.check_data()
    #     scraper_delta = time_helper.get_timedelta_from_time_periods(check_data_result['start_time'], check_data_result['end_time'])
    #     scraper_time = time_helper.convert_timedelta_to_dictionary(scraper_delta)
    # except ():
    scraper_time = None

    # End timer
    end_response_time = time.time()
    response_delta = time_helper.get_timedelta_from_time_periods(start_response_time, end_response_time)
    response_time = time_helper.convert_timedelta_to_dictionary(response_delta)

    data = {
        'response_time': response_time,
        'scraper_time': scraper_time
    }

    response = make_response(data)
    response.headers['Access-Control-Allow-Origin'] = frontendDomain
    response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
    response.headers['Vary'] = 'origin'
    response.headers['Timing-Allow-Origin'] = frontendDomain
    return response


@app.route('/update')
def update():
    wowhead_scraper = WowheadScraper()
    wowhead_scraper.update_psvs()
    return 'Updated'


@app.route('/export')
def export():
    sql_connector = SQLConnector()
    sql_connector.update_tables_from_psv()
    return 'Exported'


if __name__ == '__main__':
    app.run()
