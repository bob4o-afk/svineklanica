from __future__ import annotations

from scraper.spheres import (
    SPHERE_HEALTHCARE,
    SPHERE_JUDICIARY,
    SPHERE_POLICE,
    classify_sphere,
)


def test_classify_by_cpv():
    assert classify_sphere(None, "33000000") == SPHERE_HEALTHCARE
    assert classify_sphere(None, "85100000") == SPHERE_HEALTHCARE
    assert classify_sphere(None, "45000000") is None


def test_classify_by_authority_name():
    assert classify_sphere("МБАЛ Св. Анна", None) == SPHERE_HEALTHCARE
    assert classify_sphere("НЗОК", None) == SPHERE_HEALTHCARE
    assert classify_sphere("Районен съд Пловдив", None) == SPHERE_JUDICIARY
    assert classify_sphere("МВР - Дирекция ОП", None) == SPHERE_POLICE
    assert classify_sphere("Община Бургас", None) is None


def test_classify_precedence():
    # CPV has precedence or they both match
    assert classify_sphere("Община", "33000000") == SPHERE_HEALTHCARE
    assert classify_sphere("МВР", "33000000") == SPHERE_HEALTHCARE
