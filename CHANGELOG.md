### 0.5

Major refactor

- readyFor* methods removed
- prepare* methods replaced with before* and after*

See examples and integration test for examples of the new structure.

### 0.4

Moved logic to an abstract class to allow more work on the DatabaseRepository.

- Boolean flag for calling push() instead of save() in EloquentRepository

### 0.3
Update primarily to work with validator 0.3 and up.

- Preserve array keys on errors/getErrors (0.3.1)
- Fixed a typo causing an error (0.3.2)
- Optional action argument for makeNew to allow different types of create validation (0.3.3)
- Reset errors between each create/update (0.3.3)

### 0.2

Update primarily to work with validator 0.2 and up.

### 0.1

Initial release.