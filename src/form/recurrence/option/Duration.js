Ext.define('Extensible.form.recurrence.option.Duration', {
    extend: 'Extensible.form.recurrence.AbstractOption',
    alias: 'widget.extensible.recurrence-duration',
    
    requires: [
        'Ext.form.Label',
        'Ext.form.field.ComboBox',
        'Ext.form.field.Number',
        'Ext.form.field.Date',
    ],
    
    minOccurrences: 1,
    
    maxOccurrences: 999,
    
    maxEndDate: new Date('12/31/9999'),
    
    endDateWidth: 120,
    
    //endDateFormat: null, // inherit by default
    
    getItemConfigs: function() {
        var me = this;
        
        return [{
            xtype: 'label',
            text: 'and continuing'
        },{
            xtype: 'combo',
            itemId: me.id + '-duration-combo',
            mode: 'local',
            width: 85,
            triggerAction: 'all',
            forceSelection: true,
            value: 'forever',
            store: ['forever', 'for', 'until'],
            listeners: {
                'change': Ext.bind(me.onComboChange, me)
            }
        },{
            xtype: 'datefield',
            itemId: me.id + '-duration-date',
            showToday: false,
            width: me.endDateWidth,
            format: me.endDateFormat || Ext.form.field.Date.prototype.format,
            maxValue: me.maxEndDate,
            allowBlank: false,
            hidden: true,
            listeners: {
                'change': Ext.bind(me.onDateChange, me)
            }
        },{
            xtype: 'numberfield',
            itemId: me.id + '-duration-num',
            value: 5,
            width: 55,
            minValue: me.minOccurrences,
            maxValue: me.maxOccurrences,
            allowBlank: false,
            hidden: true,
            listeners: {
                'change': Ext.bind(me.onNumberChange, me)
            }
        },{
            xtype: 'label',
            itemId: me.id + '-duration-num-label',
            text: 'occurrences',
            hidden: true
        }]
    },
    
    initRefs: function() {
        var me = this;
        me.untilCombo = me.down('#' + me.id + '-duration-combo');
        me.untilDateField = me.down('#' + me.id + '-duration-date');
        me.untilNumberField = me.down('#' + me.id + '-duration-num');
        me.untilNumberLabel = me.down('#' + me.id + '-duration-num-label');
    },
    
    onComboChange: function(combo, rec) {
        var me = this,
            field = rec;//rec[0].data.field1;
            
        if (field == 'until') {
            me.untilDateField.show();
            if (me.untilDateField.getValue() == '') {
                me.untilDateField.setValue(me.startDate.add(Date.DAY, 5));
                me.untilDateField.setMinValue(me.startDate.clone().add(Date.DAY, 1));
            }
        }
        else {
            me.untilDateField.hide();
        }
        
        if (field == 'for') {
            me.untilNumberField.show();
            me.untilNumberLabel.show();
        }
        else {
            // recur forever
            me.untilNumberField.hide();
            me.untilNumberLabel.hide();
        }
    },
    
    onNumberChange: function(field, value, oldValue) {
        this.onChange.call(this, field, 'COUNT=' + value, 'COUNT=' + oldValue);
    },
    
    onDateChange: function(field, value, oldValue) {
        this.onChange.call(this, field, 'UNTIL=' + Ext.Date.format(value, me.dateValueFormat), 
            'UNTIL=' + oldValue);
    },
    
    setValue : function(v) {
        var me = this;
        
        if (!v) {
            me.value = undefined;
            return;
        }
        if (!me.untilCombo) {
            me.on('afterrender', function() {
                me.setValue(v);
            }, me, {single: true});
            return;
        }
        
        var parts = Ext.isArray(v) ? v : (Ext.isString(v) ? v.split(';') : v),
            value;

        Ext.each(parts, function(part) {
            if (part.indexOf('COUNT') > -1) {
                value = part.split('=')[1];
                me.value = part;
                me.untilCombo.setValue('for');
                //me.untilNumberField.setValue(value).show();
                me.untilNumberLabel.show();
            }
            else if (part.indexOf('UNTIL') > -1) {
                value = part.split('=')[1];
                me.value = part;
                me.untilCombo.setValue('until');
                //me.untilDateField.setValue(Date.parseDate(value, this.untilDateFormat)).show();
                me.untilNumberLabel.hide();
            }
        }, me);
        
        return me;
    }
})
