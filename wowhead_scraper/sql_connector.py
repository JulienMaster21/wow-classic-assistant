from mysql.connector import connect
from wowhead_scraper.exceptions import NoDBConfigFoundException, InvalidSiteVersionException, \
    CantReadCredentialException
from wowhead_scraper.scraper import WowheadScraper
import os


class SQLConnector:

    def __init__(self, site_version: str):

        # Determine site version
        if site_version in ("classic", "old"):
            self.domain = "classic"
        elif site_version == ("retail", "standard", "live"):
            self.domain = "www"
        elif site_version in ("ptr", "public test realm"):
            self.domain = "ptr"
        else:
            raise InvalidSiteVersionException

        # Initialise connection variables
        self.connection = None
        self.cursor = None

    def connect(self):

        # Get database credentials
        credentials = self.read_config()

        # Create connection
        self.connection = connect(user=credentials["user"],
                                  password=credentials["password"],
                                  database=credentials["database"],
                                  host=credentials["host"])
        self.cursor = self.connection.cursor()

        global_connect_timeout = "SET GLOBAL connect_timeout=180"
        global_wait_timeout = "SET GLOBAL connect_timeout=180"
        global_interactive_timeout = "SET GLOBAL connect_timeout=180"

        self.cursor.execute(global_connect_timeout)
        self.cursor.execute(global_wait_timeout)
        self.cursor.execute(global_interactive_timeout)

        self.connection.commit()

    def commit_changes(self):

        self.connection.commit()

    def disconnect(self):

        # Close connection
        self.cursor.close()
        self.connection.close()

    def read_config(self):

        # check if config exists
        if not os.path.isfile("wowhead_scraper/db_config"):
            raise NoDBConfigFoundException("No database config was found. "
                                           "Please make or move a text file called 'db_config' in the root folder.")
        else:
            # Open config
            with open("wowhead_scraper/db_config", "r", newline="") as config:

                # Initialise database credentials
                db_credentials = {}
                credentials_to_get = ("user", "password", "database", "host")
                for credential in credentials_to_get:

                    try:
                        db_credentials[credential] = (((config
                                                      .readline())
                                                      .replace("\r\n", ""))
                                                      .replace("\n", ""))
                    except ():
                        raise CantReadCredentialException

        return db_credentials

    def setup_db(self):

        # Execute script
        with open("wowhead_scraper/sql_scripts/setup.sql") as script:
            for result in self.cursor.execute(script.read(), multi=True):

                rowcount = result.rowcount

    def update_tables_from_psv(self):

        # Create connection
        self.connect()

        # Setup tables
        self.setup_db()

        # Disable foreign key checks
        self.cursor.execute("SET foreign_key_checks = 0;")

        # TODO setup transaction

        # Iterate through all the psv files
        psv_files = os.listdir("data")
        for file in psv_files:

            self.update_table_from_psv(file[0:-4], f"data/{file[0:-4]}")

        # Enable foreign key checks
        self.cursor.execute("SET foreign_key_checks = 1;")

        # Close connection
        self.disconnect()

    def update_table_from_psv(self, psv_name: str, psv_file_path):

        # Get psv data
        wowhead_scraper = WowheadScraper(self.domain)
        columns = wowhead_scraper.get_header_names(self.domain, psv_name)
        rows = wowhead_scraper.get_data_rows(psv_file_path)

        # Reset table
        self.reset_table(psv_name)

        # Insert rows into Database
        # Prepare columns
        columns_string = "", "".join(columns)

        for row in rows:

            # Prepare row
            current_value = 0
            row_string = ""
            for value in row:

                # Check if value is convertible
                real_value = self.check_if_string_is_convertible(value)

                # Add value to row string
                if current_value == len(row) - 1:
                    row_string += real_value
                else:
                    row_string += real_value + ", "

                current_value += 1

            self.cursor.execute(f"INSERT INTO {psv_name} ({columns_string}) VALUES ({row_string});")

        # Commit changes
        self.connection.commit()

    def check_if_string_is_convertible(self, value_string):

        # Check if string is boolean
        if value_string in ("True", "False"):
            return value_string

        # Check if string is int
        try:
            int(value_string)
            return value_string
        except ValueError:
            pass

        # Check if string is None
        if value_string == "None":
            return "NULL"

        # Default to string
        return "'" + value_string + "'"

    def reset_table(self, table_name):

        self.cursor.execute(f"TRUNCATE TABLE {table_name};")
        self.cursor.execute(f"ALTER TABLE {table_name} AUTO_INCREMENT = 0;")
