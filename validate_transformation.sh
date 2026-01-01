#!/bin/bash
# EcoVadis Plugin Transformation Validation Script
# This script checks that all key transformations were applied correctly

echo "================================"
echo "EcoVadis Transformation Validator"
echo "================================"
echo ""

PASS_COUNT=0
FAIL_COUNT=0

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

function check_pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASS_COUNT++))
}

function check_fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((FAIL_COUNT++))
}

echo "1. Checking Plugin Metadata..."
if grep -q "Plugin Name: EcoVadis Self Assessment" iso42001-gap-analysis.php; then
    check_pass "Plugin name updated to EcoVadis"
else
    check_fail "Plugin name not updated"
fi

echo ""
echo "2. Checking Question Structure..."
if grep -q "GEN200\|ENV100\|LAB100\|ETH100\|SUP100" data/questions.php; then
    check_pass "EcoVadis question IDs present"
else
    check_fail "EcoVadis question IDs not found"
fi

if grep -q "Environment\|Labor & Human Rights\|Sustainable Procurement" data/questions.php; then
    check_pass "EcoVadis themes present in questions"
else
    check_fail "EcoVadis themes not found"
fi

echo ""
echo "3. Checking Scoring Mechanism..."
if grep -q "A=100, B=50, C=0" includes/class-iso42k-scoring.php; then
    check_pass "EcoVadis scoring scale implemented"
else
    check_fail "Scoring scale not updated"
fi

if grep -q "impact.*weight" includes/class-iso42k-scoring.php; then
    check_pass "Impact weighting logic present"
else
    check_fail "Impact weighting not found"
fi

echo ""
echo "4. Checking Maturity Levels..."
if grep -q "leading.*advanced.*established.*developing.*initial" includes/class-iso42k-scoring.php -i; then
    check_pass "All 5 EcoVadis maturity levels present"
else
    check_fail "Maturity levels not complete"
fi

if grep -q "86.*71.*51.*31" includes/class-iso42k-scoring.php; then
    check_pass "EcoVadis maturity thresholds correct"
else
    check_fail "Maturity thresholds not set correctly"
fi

echo ""
echo "5. Checking AI Prompts..."
if grep -q "sustainability.*CSR.*EcoVadis" includes/class-iso42k-ai.php -i; then
    check_pass "AI prompt updated for sustainability context"
else
    check_fail "AI prompt not updated"
fi

echo ""
echo "6. Checking Frontend Templates..."
if grep -q "ECOVADIS SELF ASSESSMENT" public/templates/step-intro.php; then
    check_pass "Intro template updated"
else
    check_fail "Intro template not updated"
fi

if grep -q "Sustainability Maturity Level" public/templates/step-results.php; then
    check_pass "Results template updated"
else
    check_fail "Results template not updated"
fi

echo ""
echo "7. Checking JavaScript..."
if grep -q "A: 100, B: 50, C: 0" public/js/iso42k-flow.js; then
    check_pass "JavaScript scoring updated in flow.js"
else
    check_fail "JavaScript scoring not updated in flow.js"
fi

if grep -q "Leading.*Advanced.*Developing" public/js/iso42k-flow.js; then
    check_pass "JavaScript maturity levels updated"
else
    check_fail "JavaScript maturity levels not updated"
fi

echo ""
echo "8. Checking Email Templates..."
if grep -q "EcoVadis.*Assessment.*Results" includes/class-iso42k-email.php; then
    check_pass "Email subject lines updated"
else
    check_fail "Email subject lines not updated"
fi

echo ""
echo "================================"
echo "Validation Summary"
echo "================================"
echo -e "Passed: ${GREEN}${PASS_COUNT}${NC}"
echo -e "Failed: ${RED}${FAIL_COUNT}${NC}"
echo ""

if [ $FAIL_COUNT -eq 0 ]; then
    echo -e "${GREEN}All checks passed! ✓${NC}"
    echo "The transformation appears to be complete."
    exit 0
else
    echo -e "${RED}Some checks failed. Please review.${NC}"
    exit 1
fi
