
/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

BclDataGrid = 
{
    init : function()
    {
        Osynapsy.element('body').on('click','.bcl-datagrid-th-order-by', function(){            
            if (!this.dataset.idx) {
                return;
            }            
            let grid = this.closest('.bcl-datagrid');
            let gridId = grid.getAttribute('id');
            let orderByField = grid.querySelector('.bcl-paginator-order-by');
            let orderByString = orderByField.value;
            let curColumnIdx = this.dataset.idx;
            if (orderByString.indexOf('[' + curColumnIdx +']') > -1){
                orderByString = orderByString.replace('[' + curColumnIdx + ']','[' + curColumnIdx + ' DESC]');                
            } else if (orderByString.indexOf('[' + curColumnIdx +' DESC]') > -1) {
                orderByString = orderByString.replace('[' + curColumnIdx + ' DESC]','');                               
            } else {
                orderByString += '[' + curColumnIdx + ']';                
            }
            grid.querySelector('.bcl-paginator-current-page').value = '1';
            orderByField.value = orderByString;
            Osynapsy.refreshComponents([gridId]);
        });
        Osynapsy.element('body').on('click','.bcl-datagrid-th-check-all', function(){
            var className = $(this).data('fieldClass');
            $('.'+className).click();
        });
    }
};

if (window.Osynapsy){    
    Osynapsy.plugin.register('BclDataGrid',function(){
        BclDataGrid.init();
    });
}


