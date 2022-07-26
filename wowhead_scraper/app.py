import time

from flask import Flask, Response
from flask_restx import Api, Resource
from json import dumps
from wowhead_scraper.scraper import WowheadScraper
from wowhead_scraper.sql_connector import SQLConnector
from wowhead_scraper.time import convert_timedelta_to_dictionary, get_timedelta_from_time_periods

app = Flask(__name__)
# TODO Refactor routes to use flask restX
api = Api(version="1.0",
          title="WowheadScraper API",
          description="")

frontendDomain = "https://wow-classic-assistant.test"
resources = ("professions",
             "locations",
             "vendors",
             "reagents",
             "enchantments",
             "craftable_items",
             "profession_data",
             "specialisation_recipes",
             "all")
site_versions = ("classic",
                 "old",
                 "retail",
                 "standard",
                 "live",
                 "ptr",
                 "public test realm")


# TODO Add scraper documentation here
@app.route("/")
def documentation():

    return "Hello"


@app.route("/get/<resource>/<site_version>")
def get(resource: str, site_version: str):

    if site_version in site_versions:
        wowhead_scraper = WowheadScraper(site_version)
        resource = resource.replace("-", "_")
        if resource in resources:
            result = getattr(wowhead_scraper, f"get_{resource}")()

            return Response(response=dumps(result),
                            status=200,
                            mimetype="application/json")
        else:
            response = Response(response="Invalid scraper resource. "
                                         "See the <a href=\"/\">documentation</a> for details.",
                                status=404,
                                mimetype="text/html")
            return response
    else:
        response = Response(response="Invalid site version. See the <a href=\"/\">documentation</a> for details.",
                            status=404,
                            mimetype="text/html")
        return response


@app.route("/clear-data/<site_version>")
def clear_data(site_version: str):
    if site_version in site_versions:
        wowhead_scraper = WowheadScraper(site_version)
        wowhead_scraper.clear_data()
        response = Response(response="Data cache is cleared.",
                            status=200,
                            mimetype="text/html")
        return response
    else:
        response = Response(response="Invalid site version. See the <a href=\"/\">documentation</a> for details.",
                            status=404,
                            mimetype="text/html")
        return response


@app.route("/prepare-data/<site_version>")
def prepare_data(site_version: str):
    if site_version in site_versions:
        wowhead_scraper = WowheadScraper(site_version)
        wowhead_scraper.prepare_data_for_scraping()
        response = Response(response="Data cache is ready for scraping.",
                            status=200,
                            mimetype="text/html")
        return response
    else:
        response = Response(response="Invalid site version. See the <a href=\"/\">documentation</a> for details.",
                            status=404,
                            mimetype="text/html")
        return response


@app.route("/check-data/<site_version>")
def check_data(site_version: str):
    if site_version in site_versions:
        wowhead_scraper = WowheadScraper(site_version)
        wowhead_scraper.check_data()
    else:
        response = Response(response="Invalid site version. See the <a href=\"/\">documentation</a> for details.",
                            status=404,
                            mimetype="text/html")
        return response


@app.route("/scrape/<resource>/<site_version>")
def scrape(resource: str, site_version: str):
    if site_version in site_versions:
        resource = resource.replace("-", "_")
        if resource in resources:

            # Add scrape prefix
            resource = f"scrape_{resource}"

            # Start timer
            start_response_time = time.time()

            # Perform scraper function
            try:
                wowhead_scraper = WowheadScraper(site_version)
                result = getattr(wowhead_scraper, resource)()
                scraper_delta = get_timedelta_from_time_periods(result["start_time"],
                                                                result["end_time"])
                scraper_time = convert_timedelta_to_dictionary(scraper_delta)
            except ():
                scraper_time = None

            end_response_time = time.time()
            response_delta = get_timedelta_from_time_periods(start_response_time, end_response_time)
            response_time = convert_timedelta_to_dictionary(response_delta)

            data = {
                "response_time": response_time,
                "scraper_time": scraper_time
            }

            response = Response(response=dumps(data),
                                status=200,
                                headers={
                                    "Access-Control-Allow-Origin": frontendDomain,
                                    "Access-Control-Allow-Methods": "GET, OPTIONS",
                                    "Vary": "origin",
                                    "Timing-Allow-Origin": frontendDomain,
                                },
                                mimetype="application/json")
            return response
        else:
            response = Response(response="Invalid scraper resource. "
                                         "See the <a href=\"/\">documentation</a> for details.",
                                status=404,
                                mimetype="text/html")
            return response
    else:
        response = Response(response="Invalid site version. See the <a href=\"/\">documentation</a> for details.",
                            status=404,
                            mimetype="text/html")
        return response


@app.route("/export-to-db")
def export_to_db():
    sql_connector = SQLConnector()
    sql_connector.update_tables_from_psv()


# Errors
@app.errorhandler(403)
def no_permission(e):
    response = Response(response="You don't have permission to access this. If this is wrong, "
                                 "please contact the administrator.",
                        status=403,
                        mimetype="text/html")
    return response


@app.errorhandler(404)
def page_not_found(e):
    response = Response(response="Invalid route. See the <a href=\"/\">documentation</a> for details.",
                        status=404,
                        mimetype="text/html")
    return response


@app.errorhandler(500)
def server_error(e):
    response = Response(response="Something went wrong on our end. Sorry for the inconvenience, "
                                 "please contact the administrator.",
                        status=404,
                        mimetype="text/html")
    return response


# Initialise API
# Put API routes below here
api.init_app(app)


@api.route('/hello')
class HelloWorld(Resource):
    def get(self):
        return {
            'hello': 'world'
        }


if __name__ == "__main__":
    app.run()
