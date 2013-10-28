<?php

class Zendesk_Error extends Exception {}

/**
 * Call to the API failed
 */
class Zendesk_HttpError extends Zendesk_Error {}

/**
 * Submitted data did not pass validation
 */
class Zendesk_RecordInvalid_Error extends Zendesk_Error {}