from flask import Flask
from wowhead_scraper.scraper import WowheadScraper


app = Flask(__name__)


@app.route('/')
def hello_world():
    return 'Hello World'


@app.route('/update')
def update():
    wowhead_scraper = WowheadScraper()
    wowhead_scraper.update_psvs()
    return 'Updated'


if __name__ == '__main__':
    app.run()
