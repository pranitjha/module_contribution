This module allows the additional access restrictions based on a value of
a common field between user and entity. For now, we have a fix field name
field_access_tag which needs to be present in user and the other entity.

Now, if there are common values between both the entity, it will allow the
access else this will restrict the access.

For now, only Edit and Delete operations are associated with with this module.

If a user or other entity doesn't have a value for the access field. This
module will do nothing.

Allowed field types for field_access_tag. (taxonomy term refernce).

