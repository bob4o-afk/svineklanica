from __future__ import annotations

from analyzer.payload import view_from_record
from analyzer.routing import route_flow
from analyzer.spheres import FLOW_ASSETS, FLOW_DRUGS, FLOW_JOBS, FLOW_PROCUREMENT
from conftest import make_record


def test_route_ncpr_to_drugs():
    rec = make_record(source="ncpr", natural_key="D1", inn="Paracetamol", product="Парацетамол 500mg")
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_DRUGS


def test_route_mz_jobs_to_jobs():
    rec = make_record(
        source="mz_jobs",
        natural_key="J1",
        title="Конкурс за директор на УМБАЛ",
        category="конкурси за работа",
        sphere="здравеопазване",
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_JOBS


def test_route_mz_assets_to_assets():
    rec = make_record(
        source="mz_assets",
        natural_key="A1",
        title="Търг за продажба на автомобил",
        type="asset_disposal",
        sphere="здравеопазване",
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_ASSETS


def test_route_nhif_to_procurement():
    rec = make_record(source="nhif", natural_key="P1", title="Доставка на консумативи", sphere="здравеопазване")
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROCUREMENT


def test_route_ted_pharma_cpv_to_drugs():
    rec = make_record(source="ted", natural_key="T1", cpv="33600000-6", title="Доставка на лекарства", sphere="здравеопазване")
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_DRUGS
