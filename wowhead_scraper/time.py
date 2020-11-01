import datetime

from datetime import timedelta as timedelta
from math import floor


class TimeHelper:

    def convert_timedelta_to_dictionary(self, time_period: timedelta):

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
            'years': years,
            'months': months,
            'days': remaining_days,
            'hours': hours,
            'minutes': minutes,
            'seconds': remaining_seconds
        }

        return result

    def get_timedelta_from_time_periods(self, start_time, end_time):

        difference_in_seconds = round(end_time - start_time)
        result = datetime.timedelta(seconds=difference_in_seconds)

        return result