from flask import Flask
from flask import render_template
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import NoSuchElementException


class WowheadScraper:

    def __init__(self):

        self.driver = self.start_driver()

    def start_driver(self):

        profile = webdriver.FirefoxProfile(profile_directory='/home/julienmaster/.mozilla/firefox/yxnrqdiy.default-release')
        return webdriver.Firefox(firefox_profile=profile)


app = Flask(__name__)


@app.route('/')
def hello_world():
    return 'hello world'


if __name__ == '__main__':
    app.run()
