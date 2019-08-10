# Grid Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.2.2 - 2019-08-10
### Fixed
- Fixed an issue that occurred in Craft 3.2 when saving a grid field that has a matrix field for its target field as a draft.

## 1.2.1 - 2019-07-20
### Changed
- Raised the minimum version of Craft CMS to `^3.2.5`.
- Updated deprecated Twig Extension classes.

### Fixed
- Fixed an issue where grid fields were mismatching the IDs of target ID items.

## 1.2.0 - 2019-03-24
### Added
- Added optional classes that can be used for styling components based on where they are laid out in the grid.
- When items in a layout are shown by default (setting Item Visibility to `Visible` in field settings), you may hide the layout from the input field so you don't have to move past it during content editing.

### Changed
- Changed the way field values are stored so they no longer need to be updated when a layout’s breakpoint changes
  - This fixes an issue when saving field values into a project config
  - **This runs a migration that should be run in all environments before changing any Grid field settings**

### Fixed
- Moved grid styles for the first layout into a `max-width` media query so it doesn’t need to be overridden by later breakpoints.

## 1.1.1 - 2019-02-14
### Fixed
- Fixed a bug that occurred when adding a column in `auto` Column Mode

## 1.1.0 - 2019-02-10
### Added
- You can now create a grid field that is not tied to a target field
  - Any array—like one made from an element query—can be passed into a `{% grid %}` block and laid out by a grid field
  - Requires the `using` keyword syntax for rendering, as described here: https://github.com/wbrowar/craft-grid/blob/master/README.md#advanced-twig-options
- You can now choose whether or not items that have not been laid out onto the grid will be visible or hidden
  - This allows you to leave a layout blank and all items will be automatically added to the grid (or after the grid if there is no more room)

### Changed
- When changing a breakpoint on the field settings page, the layouts no longer re-order themselves until you are done changing the breakpoint width

### Fixed
- Added the correct prefix to let Grid fields be editable in the element editor (the popup that appears when you double-click an element)

## 1.0.1 - 2019-02-08
### Fixed
- Fixed a couple of errors that occur when no grid layout has been set before render

## 1.0.0 - 2019-02-05
### Added
- You can now use `em` or `rem` units for media queries by passing in `unit: 'em'` into the `grid` block arguments
- Added `Grid::$plugin->grid->getGridValue();` PHP method
- Added `craft.grid.value()` Twig variable

### Changed
- Made some UI tweaks on grid field layout for better usability
  - Rolling over a field item on the left highlights the corresponding item on the right
  - Starting to lay out an item highlights the label on the left and the item in the preview grid
  - Clicking on the label for an item that is being set stops the layout process
  
I‘m taking the [BETA] flag off and releasing this as 1.0.0! I‘ll keep a close eye on any issues that pop up, so please send any feedback or issues: https://github.com/wbrowar/craft-grid/issues

## 1.0.0-beta.3 - 2019-02-04
### Added
- Added support for Craft Commerce fields, Products and Variants

### Fixed
- Fixed a bug that prevented creating a new layout

## 1.0.0-beta.2 - 2019-02-03
### Fixed
- Fixed a bug that prevented creating a new grid field
- Fixed a bug that lets someone put in a negative or empty layout breakpoint
- Corrected links to docs and readme in composer.json

## 1.0.0-beta.1 - 2019-02-03
### Added
- Grid fields can now be used in Matrix fields to create multiple layouts for a target field
- Added mobile layout for settings and field input
- Grid now resaves all elements when a layout breakpoint changes in field settings

## 1.0.0-beta.0 - 2019-02-02
### Added
- Initial release
