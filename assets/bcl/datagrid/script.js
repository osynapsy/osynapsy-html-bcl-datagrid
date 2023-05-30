
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
        $('.bcl-datagrid').parent().on('click tap','.row',function(){
            if (!$(this).data('url-detail')) {
                return;
            }
            Osynapsy.History.save();
            window.location = $(this).data('url-detail');            
        }).on('click','.bcl-datagrid-th-order-by',function(){
            if (!$(this).data('idx')) {
                return;
            }            
            var gridId = $(this).closest('.bcl-datagrid').attr('id');
            var orderByField = $('.BclPaginationOrderBy','#'+gridId);
            var orderByString = orderByField.val();
            var curColumnIdx = $(this).data('idx');
            if (orderByString.indexOf('[' + curColumnIdx +']') > -1){
                orderByString = orderByString.replace('[' + curColumnIdx + ']','[' + curColumnIdx + ' DESC]');                
            } else if (orderByString.indexOf('[' + curColumnIdx +' DESC]') > -1) {
                orderByString = orderByString.replace('[' + curColumnIdx + ' DESC]','');                               
            } else {
                orderByString += '[' + curColumnIdx + ']';                
            }
            $('.BclPaginationCurrentPage','#'+gridId).val(1);
            orderByField.val(orderByString);
            Osynapsy.refreshComponents([gridId]);
        }).on('click','.bcl-datagrid-th-check-all', function(){
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


