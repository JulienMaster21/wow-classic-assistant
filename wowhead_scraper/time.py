import datetime

from datetime import timedelta as timedelta
from math import floor
from wowhead_scraper.logger import Logger


def convert_timedelta_to_dictionary(time_period: timedelta):

    # Convert days into years, months and days
    years = floor(time_period.days / 365)
    remaining_days = time_period.days % 365

    months = floor(remaining_days / 30)
    remaining_days = remaining_days % 30

    # Convert seconds in to hours, minutes and seconds
    hours = floor(time_period.seconds / 3600)
    remaining_seconds = time_period.seconds % 3600

    minutes = floor(remaining_seconds / 60)
    remaining_seconds = remaining_seconds % 60

    result = {
        "years": years,
        "months": months,
        "days": remaining_days,
        "hours": hours,
        "minutes": minutes,
        "seconds": remaining_seconds
    }

    return result


def get_timedelta_from_time_periods(start_time: float, end_time: float):

    difference_in_seconds = round(end_time - start_time)
    result = datetime.timedelta(seconds=difference_in_seconds)

    return result


def log_time(logger: Logger, start_time: float, end_time: float, action: str, name: str):

    time_string = str(get_timedelta_from_time_periods(start_time=start_time,
                                                      end_time=end_time))

    logger.log(f"It took: {time_string} to {action} the {name}.\n")
