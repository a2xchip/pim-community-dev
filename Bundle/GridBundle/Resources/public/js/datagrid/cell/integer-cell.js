/* global define */
define(['backgrid'],
function(Backgrid) {
    'use strict';

    /**
     * Integer column cell. Added missing behaviour.
     *
     * @export  oro/grid/integer-cell
     * @class   oro.grid.IntegerCell
     * @extends Backgrid.IntegerCell
     */
    return Backgrid.IntegerCell.extend({
        /**
         * @inheritDoc
         */
        enterEditMode: function (e) {
            if (this.column.get("editable")) {
                e.stopPropagation();
            }
            return Backgrid.IntegerCell.prototype.enterEditMode.apply(this, arguments);
        }
    });
});
