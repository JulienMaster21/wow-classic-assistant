# TODO Add values explaining the exceptions
class NoDBConfigFoundException(Exception):
    pass


class CantReadCredentialException(Exception):
    pass


class InvalidSiteVersionException(Exception):
    pass


class CantConvertJSONStringException(Exception):
    pass
