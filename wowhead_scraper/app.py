from flask import Flask
from wowhead_scraper.scraper import WowheadScraper
from wowhead_scraper.sql_connector import SQLConnector


app = Flask(__name__)


@app.route('/')
def hello_world():
    return 'Hello World'


@app.route('/update')
def update():
    wowhead_scraper = WowheadScraper()
    wowhead_scraper.update_psvs()
    return 'Updated'


@app.route('/export')
def export():
    sql_connector = SQLConnector()
    sql_connector.update_tables_from_psv()


if __name__ == '__main__':
    app.run()
