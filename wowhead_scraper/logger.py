import logging
import os
import time
import traceback


class Logger:

    def __init__(self):

        # Check if logs directory exists
        if not os.path.isdir("logs"):
            os.makedirs("logs")
        if not os.path.isdir("logs/scraper"):
            os.makedirs("logs/scraper")

        current_timestamp = time.time()
        logging.basicConfig(filename=f"logs/scraper/{current_timestamp}.log",
                            filemode="w",
                            format="[%(asctime)s] %(levelname)s: %(message)s",
                            datefmt="%Y-%m-%d %H:%M:%S",
                            level="DEBUG")

    def log(self, message: str):

        logging.info(message)

    def warning(self, message: str):

        logging.warning(message)

    def error(self, message: str):

        logging.error(message)

    def exception(self, exctype, value, tb):

        indent = " ".join(["" for space in range(28)])
        lines = traceback.format_exception(exctype, value, tb)
        lines = [line.replace("\n", f"\n{indent}") for line in lines]
        lines[-1] = ((lines[-1]
                     .replace("\n", ""))
                     .rstrip())
        lines[-1] = f"  {lines[-1]}"
        self.error("".join(lines))
