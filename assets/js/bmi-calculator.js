/* PowerSurge Gym — BMI Calculator (client-side only, no data sent anywhere) */
$(function () {
  'use strict';

  const $widget = $('#bmiResult');
  if ($widget.length === 0) {
    return;
  }

  let heightUnit = 'ft';
  let weightUnit = 'kg';

  // ---- Unit tab switching ----
  $('[data-height-unit]').on('click', function () {
    heightUnit = $(this).data('height-unit');
    $('[data-height-unit]').removeClass('active');
    $(this).addClass('active');
    $('[data-height-panel]').addClass('d-none');
    $('[data-height-panel="' + heightUnit + '"]').removeClass('d-none');
    maybeAutoCalculate();
  });

  $('[data-weight-unit]').on('click', function () {
    weightUnit = $(this).data('weight-unit');
    $('[data-weight-unit]').removeClass('active');
    $(this).addClass('active');
    maybeAutoCalculate();
  });

  // ---- Unit conversion + input reading ----
  function getHeightMeters() {
    if (heightUnit === 'ft') {
      const feet = parseFloat($('#bmiFeet').val());
      const inches = parseFloat($('#bmiInches').val()) || 0;
      if (!feet && !inches) return null;
      return ((feet || 0) * 12 + inches) * 0.0254;
    }
    if (heightUnit === 'in') {
      const inches = parseFloat($('#bmiHeightIn').val());
      if (!inches) return null;
      return inches * 0.0254;
    }
    const cm = parseFloat($('#bmiHeightCm').val());
    if (!cm) return null;
    return cm / 100;
  }

  function getWeightKg() {
    const value = parseFloat($('#bmiWeight').val());
    if (!value) return null;
    return weightUnit === 'lbs' ? value * 0.453592 : value;
  }

  function validate(heightM, weightKg) {
    if (heightM === null || weightKg === null) {
      return 'Please enter both your height and weight.';
    }
    if (heightM < 0.5 || heightM > 2.75) {
      return 'Please enter a realistic height.';
    }
    if (weightKg < 2 || weightKg > 400) {
      return 'Please enter a realistic weight.';
    }
    return null;
  }

  // ---- Category lookup ----
  const CATEGORIES = [
    { max: 18.5, label: 'Underweight', cls: 'bmi-underweight' },
    { max: 25, label: 'Normal', cls: 'bmi-normal' },
    { max: 30, label: 'Overweight', cls: 'bmi-overweight' },
    { max: 35, label: 'Obese Class I', cls: 'bmi-obese1' },
    { max: 40, label: 'Obese Class II', cls: 'bmi-obese2' },
    { max: Infinity, label: 'Obese Class III', cls: 'bmi-obese3' },
  ];

  function categoryFor(bmi) {
    return CATEGORIES.find((c) => bmi < c.max);
  }

  function formatWeight(kg) {
    return weightUnit === 'lbs' ? (kg / 0.453592).toFixed(1) + ' lbs' : kg.toFixed(1) + ' kg';
  }

  let lastSummary = '';

  function calculate() {
    const heightM = getHeightMeters();
    const weightKg = getWeightKg();
    const error = validate(heightM, weightKg);
    const $error = $('#bmiError');

    if (error) {
      $error.removeClass('d-none').text(error);
      $('#bmiResult').addClass('d-none');
      return;
    }
    $error.addClass('d-none').text('');

    const bmi = weightKg / (heightM * heightM);
    const prime = bmi / 25;
    const ponderal = weightKg / Math.pow(heightM, 3);
    const category = categoryFor(bmi);

    const minHealthyKg = 18.5 * heightM * heightM;
    const maxHealthyKg = 24.9 * heightM * heightM;

    let suggestion;
    if (weightKg > maxHealthyKg) {
      suggestion = 'You are about ' + Math.round(weightKg - maxHealthyKg) + ' kg above the healthy BMI range.';
    } else if (weightKg < minHealthyKg) {
      suggestion = 'You are about ' + Math.round(minHealthyKg - weightKg) + ' kg below the healthy BMI range.';
    } else {
      suggestion = "You're within the healthy BMI range. Great job!";
    }

    // ---- Update DOM ----
    $('#bmiScoreValue').text(bmi.toFixed(1));
    $('#bmiCategoryBadge').removeClass(CATEGORIES.map((c) => c.cls).join(' ')).addClass(category.cls).text(category.label);
    $('#bmiPrimeValue').text(prime.toFixed(2));
    $('#bmiRangeValue').text(formatWeight(minHealthyKg) + ' – ' + formatWeight(maxHealthyKg));
    $('#bmiSuggestionValue').text(suggestion);
    $('#bmiPonderalValue').text(ponderal.toFixed(1));

    // Progress marker: scale 15 - 40 BMI across the bar's width.
    const pct = Math.min(Math.max((bmi - 15) / (40 - 15), 0), 1) * 100;
    $('#bmiMarker').css('left', pct + '%');

    // WHO table row highlight
    $('#bmiWhoTable tbody tr').removeClass('bmi-row-active').each(function () {
      const min = parseFloat($(this).data('min'));
      const max = parseFloat($(this).data('max'));
      if (bmi >= min && bmi < max) {
        $(this).addClass('bmi-row-active');
      }
    });

    lastSummary =
      'BMI: ' + bmi.toFixed(1) + ' (' + category.label + ')\n' +
      'BMI Prime: ' + prime.toFixed(2) + '\n' +
      'Healthy Weight Range: ' + formatWeight(minHealthyKg) + ' – ' + formatWeight(maxHealthyKg) + '\n' +
      suggestion;

    $('#bmiResult').removeClass('d-none');
  }

  // Only auto-calculate once inputs look complete, so we don't flash errors mid-typing.
  function maybeAutoCalculate() {
    if (getHeightMeters() !== null && getWeightKg() !== null) {
      calculate();
    }
  }

  $('#bmiCalculateBtn').on('click', calculate);

  $('#bmiFeet, #bmiInches, #bmiHeightIn, #bmiHeightCm, #bmiWeight').on('input', maybeAutoCalculate);

  $('#bmiResetBtn').on('click', function () {
    $('#bmiFeet, #bmiInches, #bmiHeightIn, #bmiHeightCm, #bmiWeight').val('');
    $('#bmiError').addClass('d-none').text('');
    $('#bmiResult').addClass('d-none');
    $('#bmiWhoTable tbody tr').removeClass('bmi-row-active');
  });

  $('#bmiCopyBtn').on('click', function () {
    if (!lastSummary) return;
    navigator.clipboard.writeText(lastSummary).then(() => {
      const $btn = $(this);
      const original = $btn.html();
      $btn.html('<i class="bi bi-check-lg"></i> Copied!');
      setTimeout(() => $btn.html(original), 1500);
    });
  });

  $('#bmiPrintBtn').on('click', function () {
    window.print();
  });

  $('#bmiPdfBtn').on('click', function () {
    if (!lastSummary || typeof window.jspdf === 'undefined') {
      window.print();
      return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(18);
    doc.text('PowerSurge Gym — BMI Result', 14, 20);
    doc.setFontSize(12);
    doc.text(lastSummary.split('\n'), 14, 34);
    doc.save('bmi-result.pdf');
  });
});
