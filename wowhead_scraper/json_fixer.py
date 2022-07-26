from json import loads
import re
from wowhead_scraper.exceptions import CantConvertJSONStringException


def fix_json(faulty_json: str):

    # Remove extraCols
    fixed_json = faulty_json.split(',"extraCols"')[0]

    # Replace escaped double quotes with backtick
    fixed_json = re.sub(r"\\\"", r"`", fixed_json, flags=re.MULTILINE)

    # Add whitespaces after comma and colon
    fixed_json = re.sub(r",(\S)", r", \1", fixed_json, flags=re.MULTILINE)
    fixed_json = re.sub(r":(\S)", r": \1", fixed_json, flags=re.MULTILINE)

    # Replace single quote with two single quotes
    fixed_json = re.sub(r"([^']?)'([^']?)", r"\1''\2", fixed_json, flags=re.MULTILINE)

    # Add missing quotes
    # Keys
    looking_for_missing_quotes = True
    while looking_for_missing_quotes:
        fixed_json, substitutions = re.subn(r"(\[|{|, )([^{}\[\]\":, ]+): ([^~]*?)(, |}|])",
                                            r'\1"\2": \3\4',
                                            fixed_json,
                                            flags=re.MULTILINE)
        if substitutions <= 0:
            looking_for_missing_quotes = False

    # Values, but not: numeric, boolean, or null
    fixed_json = re.sub(r"\"([^{}\[\]\":, ]+)\": (?!true)(?!false)(?!null)([^{}\[\]\"\d]+)(, |}|])",
                        r'"\1": "\2"\3',
                        fixed_json,
                        flags=re.MULTILINE)

    # Turn json string into Python data
    try:
        fixed_json = loads(fixed_json)
        return fixed_json
    except Exception:
        raise CantConvertJSONStringException


def fix_data_key(string_with_data: str):

    # Wrap data key in double quotes and add space after colon if not present
    fixed_string = re.sub(r"[\"']?data[\"']?:[\s]?", r'"data": ', string_with_data, flags=re.MULTILINE)

    return fixed_string


def find_icons(icon_json: str):

    icons = {}
    icon_dict = loads(icon_json)
    for icon in icon_dict:
        name = icon_dict[icon].get("name_enus", None)
        # Use either name or wowhead id as identifier
        if name is not None:
            identifier = name
        else:
            identifier = icon
        icon_name = icon_dict[icon].get("icon", None)
        if icon_name is None:
            continue
        else:
            icons[identifier] = icon_name

    return icons


def is_url_format(value: str):

    match = re.match(r"https?://[a-zA-Z0-9.?=_/\-]+", value, flags=re.MULTILINE)
    if match is not None:
        return True
    else:
        return False
