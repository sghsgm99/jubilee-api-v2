<?php

namespace App\Http\Requests;

use App\Models\Enums\FbRuleActionEnum;
use App\Models\Enums\FbRuleConditionComparison;
use App\Models\Enums\FbRuleConditionOperator;
use App\Models\Enums\FbRuleConditionTarget;
use App\Models\Enums\FbRuleTargetEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacebookRuleAutomationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $logicalOperatorEnum = array_add(FbRuleConditionOperator::members(), 0, null);

        return [
            'name' => ['required', 'string'],
            'target' => ['required', Rule::in(FbRuleTargetEnum::members())],
            'action' => ['required', Rule::in(FbRuleActionEnum::members())],
            'hours' => ['required', 'integer'],

            // COMMON RULE CONDITION FIELDS
            'conditions' => ['required', 'array'],
            'conditions.*.target' => ['required', Rule::in(FbRuleConditionTarget::members())],
            'conditions.*.logical_operator' => ['present', Rule::in($logicalOperatorEnum)],
            'conditions.*.comparison' => [
                Rule::in(FbRuleConditionComparison::members()),
                'required_if:conditions.*.target,'.FbRuleConditionTarget::GEOGRAPHY.','.FbRuleConditionTarget::REVENUE.','.FbRuleConditionTarget::VISITORS
            ],

            // GEOGRAPHY FIELDS
            'conditions.*.address' => ['required_if:conditions.*.target,'. FbRuleConditionTarget::GEOGRAPHY],
            'conditions.*.lat' => ['required_if:conditions.*.target,'. FbRuleConditionTarget::GEOGRAPHY],
            'conditions.*.long' => ['required_if:conditions.*.target,'. FbRuleConditionTarget::GEOGRAPHY],
            'conditions.*.temperature' => [
                'required_if:conditions.*.target,'. FbRuleConditionTarget::GEOGRAPHY,
                'numeric',
                'min:1'
            ],

            // DATE FIELDS
            'conditions.*.type' => [
                'required_if:conditions.*.target,'. FbRuleConditionTarget::DATE,
                Rule::in(['range', 'exact']),
                'string'
            ],
            'conditions.*.from' => [
                'required_if:conditions.*.target,'. FbRuleConditionTarget::DATE,
                'date'
            ],
            'conditions.*.to' => [
                'required_if:conditions.*.type,range',
                'date'
            ],
            'conditions.*.recurring' => [
                'required_if:conditions.*.type,exact',
                Rule::in([0, 1])
            ],
            'conditions.*.weekly' => [
                'required_if:conditions.*.type,exact',
                Rule::in([0, 1])
            ],

            // REVENUE FIELDS
            'conditions.*.spend' => [
                'required_if:conditions.*.target,'. FbRuleConditionTarget::REVENUE,
                'numeric',
                'min:0'
            ],

            // VISITORS FIELDS
            'conditions.*.visitor_count' => [
                'required_if:conditions.*.target,'. FbRuleConditionTarget::VISITORS,
                'numeric',
                'min:0'
            ],
        ];
    }

    public function messages()
    {
        $conditionTarget = FbRuleConditionTarget::keys();
        unset($conditionTarget['DATE']);

        return [
            // COMMON RULE CONDITION FIELDS
            'conditions.*.target.required' => 'Target field is required',
            'conditions.*.target.in' => 'Target value must be one of the following: ' . implode(', ', FbRuleConditionTarget::keys()),
            'conditions.*.logical_operator.present' => 'Logic operator must be present',
            'conditions.*.logical_operator.in' => 'Logic operator value must be on of the following: ' . implode(', ', FbRuleConditionOperator::keys()),
            'conditions.*.comparison.required_if' => 'Logic comparison is required when target value is : ' . implode(', ', $conditionTarget),
            'conditions.*.comparison.in' => 'Logic comparison value must be one of the following: ' . implode(', ', FbRuleConditionComparison::keys()),

            // GEOGRAPHY FIELDS
            'conditions.*.address.required_if' => 'Address field is required when target is Geography',
            'conditions.*.lat.required_if' => 'Latitude field is required when target is Geography',
            'conditions.*.long.required_if' => 'Longitude field is required when target is Geography',
            'conditions.*.temperature.required_if' => 'Temperature field is required when target is Geography',
            'conditions.*.temperature.numeric' => 'Temperature must be a number',
            'conditions.*.temperature.min' => 'Temperature must be at least :min',

            // DATE FIELDS
            'conditions.*.type.required_if' => 'Type field is required when target is Date',
            'conditions.*.type.in' => 'Type value must be on of the following: range, exact',
            'conditions.*.from.required_if' => 'Date From field is required when target is Date',
            'conditions.*.from.date' => 'Date From is not a valid date',
            'conditions.*.to.required_if' => 'Date To field is required when type is Range',
            'conditions.*.to.date' => 'Date To is not a valid date',
            'conditions.*.recurring.required_if' => 'Event field is required when type is Exact',
            'conditions.*.recurring.in' => 'Event type must be one of the following: Recurring, One Time',
            'conditions.*.weekly.required_if' => 'Interval field is required when type is Exact',
            'conditions.*.weekly.in' => 'Interval type must be one of the following: Weekly, Daily',

            // REVENUE FIELDS
            'conditions.*.spend.required_if' => 'Spend field is required when target is Revenue',
            'conditions.*.spend.numeric' => 'Spend must be a number',
            'conditions.*.spend.min' => 'Spend must be at least :min',

            // VISITORS FIELDS
            'conditions.*.visitor_count.required_if' => 'Visitor count field is required when target is Visitors',
            'conditions.*.visitor_count.numeric' => 'Visitor count must be a number',
            'conditions.*.visitor_count.min' => 'Visitor count must be at least :min',
        ];
    }
}
