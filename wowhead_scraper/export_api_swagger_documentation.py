from flask import json
from wowhead_scraper.app import api

url_vars = False  # Build query strings in URLs
swagger = True  # Export Swagger specifications
data = api.as_postman(urlvars=url_vars, swagger=swagger)
print(json.dumps(data))
